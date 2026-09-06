<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use DOMDocument;

class SEPAGenerator implements BankFileGenerator
{
    protected string $initiatorName = 'Your Company Name';
    protected string $initiatorId = 'COMPANYID';
    protected string $iban = 'DE12345678901234567890'; // Your company IBAN
    protected string $bic = 'DEUTDEFF';               // Your company BIC

    public function generate($run): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('Document');
        $root->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $doc->appendChild($root);

        $cstmrCdtTrfInitn = $doc->createElement('CstmrCdtTrfInitn');
        $root->appendChild($cstmrCdtTrfInitn);

        // Group Header
        $grpHdr = $doc->createElement('GrpHdr');
        $cstmrCdtTrfInitn->appendChild($grpHdr);

        $msgId = $doc->createElement('MsgId', 'PAYMENT-' . $run->id . '-' . now()->format('YmdHis'));
        $grpHdr->appendChild($msgId);
        $creDtTm = $doc->createElement('CreDtTm', now()->format('Y-m-d\TH:i:s'));
        $grpHdr->appendChild($creDtTm);
        $nbOfTxs = $doc->createElement('NbOfTxs', $run->payments->count());
        $grpHdr->appendChild($nbOfTxs);
        $ctrlSum = $doc->createElement('CtrlSum', number_format($run->total_amount, 2, '.', ''));
        $grpHdr->appendChild($ctrlSum);

        // Initiating Party
        $initgPty = $doc->createElement('InitgPty');
        $nm = $doc->createElement('Nm', $this->initiatorName);
        $initgPty->appendChild($nm);
        $id = $doc->createElement('Id');
        $prvtId = $doc->createElement('PrvtId');
        $othrId = $doc->createElement('Othr');
        $othrId->appendChild($doc->createElement('Id', $this->initiatorId));
        $prvtId->appendChild($othrId);
        $id->appendChild($prvtId);
        $initgPty->appendChild($id);
        $grpHdr->appendChild($initgPty);

        // Payment Information (one per batch, all recipients in one batch)
        $pmtInf = $doc->createElement('PmtInf');
        $cstmrCdtTrfInitn->appendChild($pmtInf);

        $pmtInfId = $doc->createElement('PmtInfId', 'PAYMENT-' . $run->id);
        $pmtInf->appendChild($pmtInfId);
        $pmtMtd = $doc->createElement('PmtMtd', 'TRF');
        $pmtInf->appendChild($pmtMtd);
        $btchBookg = $doc->createElement('BtchBookg', 'true');
        $pmtInf->appendChild($btchBookg);
        $nbOfTxsPmt = $doc->createElement('NbOfTxs', $run->payments->count());
        $pmtInf->appendChild($nbOfTxsPmt);
        $ctrlSumPmt = $doc->createElement('CtrlSum', number_format($run->total_amount, 2, '.', ''));
        $pmtInf->appendChild($ctrlSumPmt);

        // Payment Type Information
        $pmtTpInf = $doc->createElement('PmtTpInf');
        $svcLvl = $doc->createElement('SvcLvl');
        $svcLvl->appendChild($doc->createElement('Cd', 'SEPA'));
        $pmtTpInf->appendChild($svcLvl);
        $pmtInf->appendChild($pmtTpInf);

        // Requested Execution Date
        $reqdExctnDt = $doc->createElement('ReqdExctnDt', $run->payment_date?->format('Y-m-d') ?? now()->addDays(2)->format('Y-m-d'));
        $pmtInf->appendChild($reqdExctnDt);

        // Debtor (Company)
        $dbtr = $doc->createElement('Dbtr');
        $dbtr->appendChild($doc->createElement('Nm', $this->initiatorName));
        $pmtInf->appendChild($dbtr);

        $dbtrAcct = $doc->createElement('DbtrAcct');
        $idAcct = $doc->createElement('Id');
        $idAcct->appendChild($doc->createElement('IBAN', $this->iban));
        $dbtrAcct->appendChild($idAcct);
        $pmtInf->appendChild($dbtrAcct);

        $dbtrAgt = $doc->createElement('DbtrAgt');
        $finInstnId = $doc->createElement('FinInstnId');
        $finInstnId->appendChild($doc->createElement('BIC', $this->bic));
        $dbtrAgt->appendChild($finInstnId);
        $pmtInf->appendChild($dbtrAgt);

        // Credit Transfer Transaction Information per recipient
        foreach ($run->payments as $payment) {
            $recipient = $payment->recipient;
            $bankAccount = $recipient->bankAccount;
            if (!$bankAccount || !$bankAccount->bank_iban) {
                continue; // skip recipients without IBAN
            }

            $cdtTrfTxInf = $doc->createElement('CdtTrfTxInf');

            $pmtId = $doc->createElement('PmtId');
            $pmtId->appendChild($doc->createElement('EndToEndId', 'PAY-' . $run->id . '-' . $recipient->id));
            $cdtTrfTxInf->appendChild($pmtId);

            $amt = $doc->createElement('Amt');
            $instdAmt = $doc->createElement('InstdAmt', number_format($payment->amount, 2, '.', ''));
            $instdAmt->setAttribute('Ccy', 'EUR');
            $amt->appendChild($instdAmt);
            $cdtTrfTxInf->appendChild($amt);

            $cdtr = $doc->createElement('Cdtr');
            $cdtr->appendChild($doc->createElement('Nm', substr($bankAccount->bank_account_name ?? $recipient->full_name, 0, 70)));
            $cdtTrfTxInf->appendChild($cdtr);

            $cdtrAcct = $doc->createElement('CdtrAcct');
            $idAcctCred = $doc->createElement('Id');
            $idAcctCred->appendChild($doc->createElement('IBAN', $bankAccount->bank_iban));
            $cdtrAcct->appendChild($idAcctCred);
            $cdtTrfTxInf->appendChild($cdtrAcct);

            $cdtrAgt = $doc->createElement('CdtrAgt');
            $finInstnIdCred = $doc->createElement('FinInstnId');
            if ($bankAccount->bank_swift) {
                $finInstnIdCred->appendChild($doc->createElement('BIC', $bankAccount->bank_swift));
            }
            $cdtrAgt->appendChild($finInstnIdCred);
            $cdtTrfTxInf->appendChild($cdtrAgt);

            $pmtInf->appendChild($cdtTrfTxInf);
        }

        return $doc->saveXML();
    }

    public function getFileName($run): string
    {
        return "sepa_{$run->id}_{$run->end_date->format('Ymd')}.xml";
    }

    public function getMimeType(): string
    {
        return 'application/xml';
    }
}
