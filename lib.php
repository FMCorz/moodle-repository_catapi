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
 * Repository CatAPI lib.
 *
 * @package    repository
 * @subpackage catapi
 * @copyright  2014 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Repository CatAPI class.
 *
 * @package    repository
 * @subpackage catapi
 * @copyright  2014 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_catapi extends repository {

    /** API base URL. */
    const API = "http://thecatapi.com/api";

    /**
     * Build the URL for the request.
     *
     * @param string $uri URI.
     * @param array $params URL parameters.
     * @return moodle_url The URL.
     */
    protected function build_request($uri, array $params = null) {
        $url = new moodle_url(self::API . '/' . trim($uri, '/'));
        $url->params($params);
        return $url;
    }

    /**
     * Check if the user is authenticated in this repository or not.
     *
     * @return bool true when logged in, false when not
     */
    public function check_login() {
        return true;
    }

    /**
     * Return lots of cats.
     *
     * @param string $path Path.
     * @param string $page Page.
     * @return array of results.
     */
    public function get_listing($path = '', $page = '') {
        global $OUTPUT;

        $result = array(
            'list' => array(),
            'dynload' => true,
            'nologin' => true,
            'norefresh' => true,
            'nosearch' => true,
            'page' => $page ? $page : 1,
            'pages' => -1,
        );

        $request = $this->request('/images/get', array('format' => 'xml', 'results_per_page' => 20));
        $oldentity = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($request);
        libxml_disable_entity_loader($oldentity);

        if (!$xml->data || !$xml->data->images) {
            throw new moodle_exception('No images found.');
        }

        $images = array();
        foreach ($xml->data->images->image as $image) {
            $ext = substr($image->url, strrpos($image->url, '.'));
            $images[] = array(
                'title' => $image->id . $ext,
                'source' => (string) $image->url,
                'thumbnail' => $this->build_request('/images/get', array('image_id' => $image->id, 'size' => 'small'))->out(false),
                'thumbnail_height' => 64,
                'thumbnail_width' => 64
            );
        }

        $result['list'] = $images;

        return $result;
    }

    /**
     * Send a request to the API.
     *
     * @param string $uri URI.
     * @param array $params URL parameters.
     * @return string Response.
     */
    protected function request($uri, array $params = null) {
        $url = $this->build_request($uri, $params);
        $curl = new curl();
        $content = $curl->get($url->out(false));
        if (!empty($curl->error)) {
            throw new moodle_exception("Request to $uri failed.");
        }
        return $content;
    }

}
