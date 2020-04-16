<?php

/*
 * Exerice PHP : https://gist.github.com/f2r/2f1e1fa27186ac670c21d8a0303aabf1
 *
 * Solution de Nathanaël Martel
 */

function getFieldsDescription($description)
{
    $descriptions_field = [];

    if (false !== $description) {
        if (!file_exists($description)) {
            return $descriptions_field;
        }

        $descriptions_lines = explode("\n", file_get_contents($description));
        foreach ($descriptions_lines as $line) {
            $line = trim($line);

            // ignore empty lines
            if ('' == $line) {
                continue;
            }

            // ignore comments
            $line_pieces = explode('#', $line);
            $line = trim($line_pieces[0]);
            if ('' == $line) {
                continue;
            }

            $line_pieces = explode('=', $line);
            if (isset($line_pieces[1])) {
                $descriptions_field[trim($line_pieces[0])] = trim($line_pieces[1]);
            }
        }
    }

    return $descriptions_field;
}

function getSelectedFieldsDescription($descriptions_field, $fields, $file_fields)
{
    // chech selected fields exist in csv
    foreach ($fields as $k => $field) {
        if (!in_array($field, $file_fields)) {
            unset($fields[$k]);
        }
    }

    // if there is no selected field, use all of the file
    if (0 == count($fields)) {
        $fields = $file_fields;
    }

    // set field params
    foreach ($fields as $field) {
        if (in_array($field, $file_fields)) {
            if (isset($descriptions_field[$field])) {
                $output_fields[$field] = [
                    'type' => ltrim($descriptions_field[$field], '?'),
                    'nullable' => ('?' == substr($descriptions_field[$field], 0, 1)) ? true : false,
                ];
            } else {
                $output_fields[$field] = [
                    'type' => 'string',
                    'nullable' => false,
                ];
            }
        }
    }

    return $output_fields;
}

function findSeparator($input_file)
{
    $handle = fopen($input_file, 'r');
    $separators = [',', ';', "\t", '|', ' '];

    foreach ($separators as $separator) {
        $data = fgetcsv($handle, 1000, $separator);
        if (count($data) > 1) {
            fclose($handle);

            return $separator;
        }
    }
}

function convertData($value, $field, $line = null, $col = null)
{
    $error_message_position = sprintf('in CSV at line %s, col "%s"', $line, $col);

    if (('' == $value) && $field['nullable']) {
        return null;
    }

    if ('' == $value) {
        throw new Exception(sprintf('Empty value is not accepted %s', $error_message_position));
    }

    if ('string' == $field['type']) {
        return $value;
    }

    if (('int' == $field['type']) || ('integer' == $field['type'])) {
        return (int) $value;
    }

    if ('float' == $field['type']) {
        return (float) $value;
    }

    if (('bool' == $field['type']) || ('boolean' == $field['type'])) {
        $true_values = ['true', '1', 'on', 'yes'];
        if (in_array($value, $true_values)) {
            return true;
        }

        $false_values = ['false', '0', 'off', 'no'];
        if (in_array($value, $false_values)) {
            return false;
        }
        if ($field['nullable']) {
            return null;
        }

        throw new Exception(sprintf('Value "%s" is not Boolean %s. Accepted value are: %s, %s', $value, $error_message_position, implode(', ', $true_values), implode(', ', $false_values)));
    }

    if (('date' == $field['type']) || ('datetime' == $field['type']) || ('time' == $field['type'])) {
        try {
            $value = new DateTime($value);
        } catch (Exception $e) {
            if ($field['nullable']) {
                return null;
            }
            throw new Exception(sprintf('Value "%s" is not date or time format %s.', $value, $error_message_position));
        }

        if ('date' == $field['type']) {
            return $value->format('Y-m-d');
        }

        if ('time' == $field['type']) {
            return $value->format('H:i:s');
        }

        return $value->format('Y-m-d H:i:s');
    }

    return $value;
}
