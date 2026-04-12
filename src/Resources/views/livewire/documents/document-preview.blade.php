<div class="document-preview-container w-100" style="min-height: 500px;">
    @if($fileType === 'image')
        @include('qf::livewire.documents.partials.preview-image')
    @elseif($fileType === 'pdf')
        @include('qf::livewire.documents.partials.preview-pdf')
    @elseif($fileType === 'text')
        @include('qf::livewire.documents.partials.preview-text')
    @elseif($fileType === 'office')
        @include('qf::livewire.documents.partials.preview-office')
    @else
        @include('qf::livewire.documents.partials.preview-unsupported')
    @endif
</div>