<?php

namespace QuickerFaster\UILibrary\Console\Support;

/**
 * Token-based manipulation of a Laravel User model source file.
 *
 * The install command needs to add `use TraitName;` statements to the class
 * body of the consuming app's User model and, where necessary, the matching
 * fully-qualified `use` import at the top of the file. Regular-expression
 * string replacement is too fragile for this (for example, Laravel's default
 * `App\Models\User` extends `Authenticatable` whose class basename is also
 * `User`, which breaks naive `class User ... extends User` regexes).
 *
 * This class uses PHP's built-in tokeniser (`token_get_all`) so it is
 * independent of formatting, whitespace and the parent class name. It has no
 * Laravel dependencies, which keeps it easy to unit test in isolation.
 */
final class UserModelTraitInjector
{
    /**
     * Inject the given trait FQCNs into a User model source file.
     *
     * @param  string  $contents   Raw PHP source of the User model.
     * @param  string  $className  Short class name of the User model (e.g. "User").
     * @param  string[]  $traits   Fully-qualified trait names.
     * @return array{contents: string, modified: bool, error: ?string}
     */
    public static function inject(string $contents, string $className, array $traits): array
    {
        $analysis = self::analyze($contents, $className);

        if ($analysis['class_brace_index'] === null) {
            return [
                'contents' => $contents,
                'modified' => false,
                'error' => "Could not locate the `{$className}` class body in the source file.",
            ];
        }

        $bodyUses = '';
        $importLines = '';

        foreach ($traits as $trait) {
            $shortName = self::shortName(ltrim($trait, '\\'));

            if (!isset($analysis['used_short_names'][$shortName])) {
                $bodyUses .= "\n    use {$shortName};";
            }

            if (!isset($analysis['imported_short_names'][$shortName])) {
                $importLines .= "\nuse " . ltrim($trait, '\\') . ';';
            }
        }

        if ($bodyUses === '' && $importLines === '') {
            return ['contents' => $contents, 'modified' => false, 'error' => null];
        }

        // Apply the class-body insertion first: it lives further down the file
        // than the imports, so offsets taken from the original contents remain
        // valid when we subsequently insert the imports above it.
        if ($bodyUses !== '') {
            $contents = substr_replace($contents, $bodyUses, $analysis['class_body_start'], 0);
        }

        if ($importLines !== '') {
            $insertPos = $analysis['last_import_end_pos']
                ?? $analysis['namespace_end_pos']
                ?? $analysis['open_tag_end_pos']
                ?? 0;

            $contents = substr_replace($contents, $importLines, $insertPos, 0);
        }

        return ['contents' => $contents, 'modified' => true, 'error' => null];
    }

    /**
     * Check whether the class body already uses a given trait, purely from
     * the source text (the in-memory class may be stale during an install).
     *
     * @param  string  $contents  Raw PHP source of the User model.
     * @param  string  $className Short class name of the User model.
     * @param  string  $trait     Fully-qualified trait name.
     */
    public static function usesTrait(string $contents, string $className, string $trait): bool
    {
        $analysis = self::analyze($contents, $className);
        $shortName = self::shortName(ltrim($trait, '\\'));

        return isset($analysis['used_short_names'][$shortName]);
    }

    /**
     * Tokenise a PHP source file and return the locations and identifiers
     * needed to safely inject trait `use` statements.
     *
     * @return array{
     *     class_brace_index: ?int,
     *     class_body_start: ?int,
     *     namespace_end_pos: ?int,
     *     last_import_end_pos: ?int,
     *     open_tag_end_pos: ?int,
     *     imported_short_names: array<string, bool>,
     *     used_short_names: array<string, bool>
     * }
     */
    public static function analyze(string $contents, string $className): array
    {
        $tokens = token_get_all($contents);
        $count = count($tokens);

        $starts = [];
        $pos = 0;
        for ($i = 0; $i < $count; $i++) {
            $starts[$i] = $pos;
            $pos += strlen(self::tokenText($tokens[$i]));
        }

        $classBraceIndex = null;
        $classBodyStart = null;
        $openTagEndPos = null;

        if ($count > 0 && self::tokenId($tokens[0]) === T_OPEN_TAG) {
            $openTagEndPos = $starts[0] + strlen(self::tokenText($tokens[0]));
        }

        // Locate the named class and its opening brace.
        for ($i = 0; $i < $count; $i++) {
            if (self::tokenId($tokens[$i]) !== T_CLASS) {
                continue;
            }

            // Ignore `SomeClass::class` constant references.
            if ($i > 0 && self::tokenId($tokens[$i - 1]) === T_DOUBLE_COLON) {
                continue;
            }

            $declaredClass = null;
            for ($j = $i + 1; $j < $count; $j++) {
                $id = self::tokenId($tokens[$j]);

                if ($id === T_STRING) {
                    $declaredClass = self::tokenText($tokens[$j]);
                    break;
                }

                $text = self::tokenText($tokens[$j]);
                if ($id === null && in_array($text, ['(', '{', ';'], true)) {
                    break;
                }
            }

            if ($declaredClass !== $className) {
                continue;
            }

            $braceIndex = self::findTokenIndex($tokens, $i + 1, $count, '{');
            if ($braceIndex === null) {
                continue;
            }

            $classBraceIndex = $braceIndex;
            $classBodyStart = $starts[$braceIndex] + 1;
            break;
        }

        $namespaceEndPos = null;
        $lastImportEndPos = null;
        $importedShortNames = [];

        if ($classBraceIndex !== null) {
            for ($i = 0; $i < $classBraceIndex; $i++) {
                $id = self::tokenId($tokens[$i]);

                if ($id === T_NAMESPACE) {
                    $semi = self::findTokenIndex($tokens, $i + 1, $classBraceIndex, ';');
                    if ($semi !== null) {
                        $namespaceEndPos = $starts[$semi] + strlen(self::tokenText($tokens[$semi]));
                    }
                }

                if ($id === T_USE) {
                    $semi = self::findTokenIndex($tokens, $i + 1, $classBraceIndex, ';');
                    if ($semi !== null) {
                        $lastImportEndPos = $starts[$semi] + strlen(self::tokenText($tokens[$semi]));

                        $importTokens = array_slice($tokens, $i, $semi - $i + 1);
                        $short = self::resolveImportedShortName($importTokens);
                        if ($short !== null) {
                            $importedShortNames[$short] = true;
                        }

                        $i = $semi;
                    }
                }
            }
        }

        $usedShortNames = [];
        if ($classBraceIndex !== null) {
            $classBodyTokens = array_slice($tokens, $classBraceIndex + 1);
            $bodyCount = count($classBodyTokens);

            for ($i = 0; $i < $bodyCount; $i++) {
                if (self::tokenId($classBodyTokens[$i]) !== T_USE) {
                    continue;
                }

                for ($j = $i + 1; $j < $bodyCount; $j++) {
                    $text = self::tokenText($classBodyTokens[$j]);

                    if ($text === ';' || $text === '{') {
                        break;
                    }

                    if (self::tokenId($classBodyTokens[$j]) === T_STRING) {
                        $usedShortNames[$text] = true;
                    }
                }
            }
        }

        return [
            'class_brace_index' => $classBraceIndex,
            'class_body_start' => $classBodyStart,
            'namespace_end_pos' => $namespaceEndPos,
            'last_import_end_pos' => $lastImportEndPos,
            'open_tag_end_pos' => $openTagEndPos,
            'imported_short_names' => $importedShortNames,
            'used_short_names' => $usedShortNames,
        ];
    }

    /**
     * @param  array|string  $token
     */
    private static function tokenText($token): string
    {
        return is_array($token) ? $token[1] : $token;
    }

    /**
     * @param  array|string  $token
     */
    private static function tokenId($token): ?int
    {
        return is_array($token) ? $token[0] : null;
    }

    /**
     * @param  array[]|string[]  $tokens
     */
    private static function findTokenIndex(array $tokens, int $from, int $to, string $text): ?int
    {
        for ($i = $from; $i < $to; $i++) {
            if (!is_array($tokens[$i]) && $tokens[$i] === $text) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Determine the short name an import statement introduces.
     *
     * @param  array[]|string[]  $importTokens
     */
    private static function resolveImportedShortName(array $importTokens): ?string
    {
        $segments = [];

        foreach ($importTokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            $id = $token[0];

            if ($id === T_STRING || $id === T_NAME_QUALIFIED || $id === T_NAME_FULLY_QUALIFIED) {
                $name = $token[1];
                $backslash = strrpos($name, '\\');
                $segments[] = $backslash === false ? $name : substr($name, $backslash + 1);
            }
        }

        return $segments ? end($segments) : null;
    }

    /**
     * Get the short class/trait name from a fully-qualified name without
     * relying on Laravel's `class_basename()` helper.
     */
    private static function shortName(string $class): string
    {
        $backslash = strrpos($class, '\\');

        return $backslash === false ? $class : substr($class, $backslash + 1);
    }
}
