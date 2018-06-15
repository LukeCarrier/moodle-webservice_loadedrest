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

use external_description;

defined('MOODLE_INTERNAL') || die;

/**
 * Base format implementation.
 *
 * Contains just enough logic
 */
abstract class abstract_format implements format {
    /**
     * @inheritdoc
     */
    public function transform_parameters($parameters, external_description $description) {
        return $parameters;
    }

    /**
     * @inheritdoc
     */
    public function send_headers() {
        $this->send_access_control_headers();
        $this->send_cache_control_headers();
        $this->send_accept_headers();
    }

    /**
     * Send cache control headers.
     *
     * @return void
     */
    protected function send_cache_control_headers() {
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('Pragma: no-cache');
    }

    /**
     * Send access control headers.
     *
     * @return void
     */
    protected function send_access_control_headers() {
        header('Access-Control-Allow-Origin: *');
    }

    /**
     * Send accept headers.
     *
     * @return void
     */
    protected function send_accept_headers() {
        header('Accept-Ranges: none');
    }
}
