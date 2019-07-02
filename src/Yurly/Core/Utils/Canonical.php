<?php declare(strict_types=1);

namespace Yurly\Core\Utils;

use Yurly\Core\{Caller, Url};
use Yurly\Core\Exception\URLParseException;

class Canonical
{

    protected static $lastError;

    /**
     * Convert a URL template in format /path/to/:var(/:var2(/:var3)) to a parseable
     * regular expression. Optionally returns the keys.
     */
    public static function templateToRegex(string $urlTemplate, ?array &$keys = [], ?array &$requiredKeys = []): string
    {

        $keys = !is_array($keys) ? [] : $keys;
        $requiredKeys = !is_array($requiredKeys) ? [] : $requiredKeys;

        // Remove all characters we don't understand
        if (preg_replace('/[^A-Za-z0-9():_.\/]/', '', $urlTemplate) != $urlTemplate) {
            throw new URLParseException("Canonical docblock contains unparseable characters.");
        }

        return '/^' . str_replace('/', '\/',
            preg_replace_callback('/([\/\(]*)(\()?:([A-Za-z0-9_.]+)([\)]*)?/',
                function($matches) use (&$keys, &$requiredKeys) {
                    if ($matches[1] == '/') {
                        $keys[] = $matches[3];
                        $requiredKeys[] = $matches[3];
                        return '/([A-Za-z0-9_%+.-]+)';
                    } else
                    if ($matches[1] == '/(' || $matches[1] == '(/') {
                        $keys[] = $matches[3];
                        return '/?([A-Za-z0-9_%+.-]+)?';
                    } else {
                        throw new URLParseException(sprintf("Unidentifiable character surrounds route parameter '%s'", $matches[3]));
                    }
                }, $urlTemplate
            )) . '$/i';

    }

    /**
     * Replaces the parameters into the specified template
     */
    public static function replaceIntoTemplate(string $urlTemplate, ?array $params = []): string
    {

        return
            preg_replace_callback('/([\/\(]*)(\()?:([a-z0-9]+)([\)]*)?/',
                function($matches) use ($params) {
                    if (($matches[1] == '/') || ($matches[1] == '(/')) {
                        return (isset($params[$matches[3]]) ? '/' . $params[$matches[3]] : '');
                    }
                }, $urlTemplate
            );

    }

    /**
     * Pass in a canonical annotation and url and we'll extract variables
     */
    public static function extract(Caller $caller, Url $url): array
    {

        $urlTemplate = $caller->annotations['canonical'];
        $requestUri = $url->requestUri;

        self::$lastError = null;

        $regex = static::templateToRegex($urlTemplate, $keys, $requiredKeys);

        if (preg_match($regex, $requestUri, $matches) !== 0) {
            $values = array_combine($keys,
                array_pad(array_slice($matches, 1), count($keys), null)
            );
            foreach($values as $key => $value) {
                $values[$key] = $value ? urldecode($value) : null;
            }
            return $values;
        }

        self::$lastError = sprintf("Request URI '%s' does not match canonical template for method '%s'.", $requestUri, $caller->method);

        return []; // return empty array

    }

    /**
     * Return last error
     */
    public static function getLastError(): ?string
    {

        return self::$lastError;

    }

}
