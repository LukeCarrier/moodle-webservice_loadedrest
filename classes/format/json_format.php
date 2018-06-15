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
 * JSON format.
 */
class json_format extends abstract_format implements format {
    /**
     * @inheritdoc
     */
    public function get_name() {
        return 'json';
    }

    /**
     * @inheritdoc
     */
    public function serialise($params) {
        return json_encode($params);
    }

    /**
     * @inheritdoc
     */
    public function deserialise($body) {
        json_decode('[]');
        $result = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new invalid_parameter_exception(
                    'request body could not be parsed as valid json');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function send_headers() {
        parent::send_headers();
        header('Content-Type: application/json');
    }

    /**
     * @inheritdoc
     */
    public function send_error(Exception $exception) {
        $error = (object) [
            'success' => false,
            'exception' => (object) [
                'class' => get_class($exception),
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],
        ];

        if (debugging() && property_exists($exception, 'debuginfo')) {
            /** @noinspection PhpUndefinedFieldInspection */
            $error->exception->debug = $exception->debuginfo;
        }

        echo json_encode($error);
    }

    /**
     * @inheritdoc
     */
    public function send_response($result, external_description $description) {
        echo json_encode($result);
    }
}
