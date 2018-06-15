<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Loaded REST.
 *
 * @package webservice_loadedrest
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2018 Luke Carrier
 */

namespace webservice_loadedrest\format;

use coding_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Format factory.
 */
class format_factory {
    /**
     * Format class name format.
     *
     * @var string
     */
    const CLASS_NAME_FORMAT = '\\webservice_loadedrest\\format\\%s_format';

    /**
     * Default body format name.
     *
     * @var string
     */
    const FORMAT_DEFAULT = 'json';

    /**
     * Create an instance of the named format.
     *
     * @param string $name
     * @return format
     * @throws coding_exception when supplied an invalid format name
     */
    public static function create($name) {
        $classname = sprintf(static::CLASS_NAME_FORMAT, $name);

        if (!class_exists($classname)) {
            throw new coding_exception(sprintf('invalid format "%s"'), $name);
        }

        return new $classname();
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Create an instance of the named format, falling back to the default.
     *
     * @param string $name
     * @return format
     */
    public static function create_or_default($name) {
        try {
            $format = format_factory::create($name);
        } catch (coding_exception $e) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $format = format_factory::create(static::FORMAT_DEFAULT);
            debugging(sprintf(
                    'loaded rest: invalid format "%s"; falling back to default "%s"',
                    $name), static::FORMAT_DEFAULT);
        }
        return $format;
    }
}
