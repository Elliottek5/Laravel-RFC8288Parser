<?php

namespace App\Support;

use Exception;

class RFC8288Parser
{
    /**
     * Parses the link header and returns an array of links.
     *
     * @param  string  $header The link header to parse.
     * @return array An array of links, where the key is the link relation and the value is the link target.
     *
     * @throws Exception If there is an error parsing the link header.
     */
    public function parseLinkHeader($header)
    {
        $links = [];
        $linkValues = $this->parseCommaSeparatedValues($header);

        foreach ($linkValues as $value) {
            $link = $this->parseLinkValue($value);
            if ($link !== null) {
                $links[$link['parameters']['rel']] = $this->getUrlSubString($link['target']);
            }
        }

        return $links;
    }

    /**
     * Parses a comma-separated string and returns an array of values.
     *
     * @param  string  $input The comma-separated string to parse.
     * @return array An array of values.
     */
    private function parseCommaSeparatedValues($input)
    {
        $values = [];
        $current = '';
        $inQuote = false;

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];

            if ($char === ',' && ! $inQuote) {
                $values[] = trim($current);
                $current = '';
            } else {
                $current .= $char;

                if ($char === '"') {
                    $inQuote = ! $inQuote;
                }
            }
        }

        $values[] = trim($current);

        return $values;
    }

    /**
     * Parses the link value and extracts the target URI and parameters.
     *
     * @param  mixed  $input The input value to be parsed.
     * @return array|null Returns an array containing the target URI and parameters, or null if the input is invalid.
     */
    private function parseLinkValue($input)
    {
        $link = [];
        $parameters = [];
        $input = trim($input);

        // Extract target URI
        $targetStart = strpos($input, '<');
        $targetEnd = strpos($input, '>');
        if ($targetStart === false || $targetEnd === false || $targetStart >= $targetEnd) {
            return null;
        }

        $link['target'] = trim(substr($input, $targetStart + 1, $targetEnd - $targetStart - 1));

        // Extract parameters
        $parameterString = substr($input, $targetEnd + 1);
        $parameterPairs = $this->parseParameters($parameterString);

        foreach ($parameterPairs as $pair) {
            [$name, $value] = $pair;
            $parameters[$name] = $value;
        }

        $link['parameters'] = $parameters;

        return $link;
    }

    /**
     * Parses the parameters of the input string and returns an array of parameter-value pairs.
     *
     * @param  string  $input The input string containing the parameters.
     * @return array An array of parameter-value pairs.
     */
    private function parseParameters($input)
    {
        $parameters = [];
        $input = trim($input);

        if (! empty($input)) {
            // Extract parameter name
            $nameEnd = strcspn($input, "=\t\r\n");
            $nameUntrimmed = strtolower(trim(substr($input, 0, $nameEnd)));
            $input = strtolower(trim(substr($input, $nameEnd), ";\t\r\n"));
            $name = ltrim(trim($nameUntrimmed, ";\t\r\n"));

            // Extract parameter value
            $value = '';

            if (! empty($input) && $input[0] === '=') {
                $input = ltrim(substr($input, 1), " \t\r\n");

                if (! empty($input)) {
                    if (strpos($input, '"') !== false) {
                        $value = $this->parseQuotedString($input);
                    } else {
                        $valueEnd = strcspn($input, ";\t\r\n");
                        $value = substr($input, 0, $valueEnd);
                        $input = ltrim(substr($input, $valueEnd), ";\t\r\n");

                    }
                }
            }

            $parameters[] = [$name, $value];
        }

        return $parameters;
    }

    /**
     * Parses a quoted string from the given input.
     *
     * @param  string  &$input The input string to parse.
     * @return string The parsed quoted string.
     */
    private function parseQuotedString(&$input)
    {
        $output = '';

        if ($input[0] !== '"') {
            return $output;
        }

        // Discard the first character
        $input = substr($input, 1);

        if ($input[0] === '\\') {
            // Discard the backslash and next character
            $output .= $input[1] ?? '';
            $input = substr($input, 2);
            dd($output, $input);
        } elseif (str_ends_with($input, '"') === true) {
            // Discard the closing double quote
            return substr($input, 0, strpos($input, '"'));
        } else {
            // Consume the next character
            $output .= $input[0];
            $input = substr($input, 1);
        }

        return $output;
    }

    /**
     * Get the substring of a URL starting from the last occurrence of '?'.
     *
     * @param  string  $url The URL to extract the substring from.
     * @return string The extracted substring.
     */
    private function getUrlSubString(string $url)
    {
        return substr($url, strrpos($url, '?'));
    }
}
