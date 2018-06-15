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

use Exception;
use external_description;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Web service format.
 */
interface format {
    /**
     * Get the WS format name.
     *
     * @return string
     */
    public function get_name();

    /**
     * Serialise a request body from parameters.
     *
     * @param mixed[] $params
     *
     * @return string
     */
    public function serialise($params);

    /**
     * Parse request body.
     *
     * @param string $body
     *
     * @return mixed
     *
     * @throws invalid_parameter_exception
     */
    public function deserialise($body);

    /**
     * Send HTTP response headers.
     *
     * Headers, with the exception of the status line, are expected to be
     * consistent across both successful responses and error responses. If we
     * were asked to return JSON, we will not return errors in HTML.
     *
     * @return void
     */
    public function send_headers();

    /**
     * Send an error.
     *
     * @return void
     */
    public function send_error(Exception $exception);

    /**
     * Send a response.
     *
     * @param mixed $result
     * @param external_description $description
     *
     * @return void
     */
    public function send_response($result, external_description $description);
}
