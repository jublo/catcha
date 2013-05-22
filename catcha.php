<?php

/**
 * Catch-a spammer with a CAPTCHA.
 *
 * @package catcha
 * @version 0.1.0-dev
 * @author J.M. <me@mynetx.net>
 * @copyright 2013 J.M. <me@mynetx.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Main Catcha class
 *
 * @package catcha
 */

class Catcha
{
    /**
     * Font file to use for challenge image
     *
     * @access protected
     */
    protected $_imageFont;

    /**
     * Height of the challenge image canvas
     *
     * @access protected
     */
    protected $_imageHeight;

    /**
     * Width of the challenge image canvas
     *
     * @access protected
     */
    protected $_imageWidth;

    /**
     * Challenge equation to ask
     *
     * @access protected
     */
    protected $_equation;

    /**
     * Challenge result
     *
     * @access protected
     */
    protected $_result;

    /**
     * Class constructor
     *
     * @param void
     *
     * @return void
     */
    public function __construct()
    {
        // check for required extensions
        $this->_checkExtensions();

        // initialize member variables
        $this->setImageSize(100, 25);
        $this->setImageFont(dirname(__FILE__) . '/font/Averia-Light.ttf');

        // generate the challenge data
        $this->newChallenge();
    }

    /**
     * Get the canvas size of the challenge image
     *
     * @return array($width, $height) The canvas size
     */
    public function getImageSize()
    {
        return array($this->_imageWidth, $this->_imageHeight);
    }

    /**
     * Set the canvas size of the challenge image
     *
     * @param int $width  The image width (>= 30px)
     * @param int $height The image height (>= 10px)
     *
     * @return void
     */
    public function setImageSize($width, $height)
    {
        // validate
        $width = intval($width);
        $height = intval($height);
        if ($width < 30 || $height < 10) {
            throw new Exception('setImageSize: invalid parameters');
        }

        // store given values
        $this->_imageWidth = $width;
        $this->_imageHeight = $height;
    }

    /**
     * Get the font file to be used for drawing the challenge characters
     *
     * @return string $font_file The full path to font file (*.ttf)
     */
    public function getImageFont()
    {
        return $this->_imageFont;
    }

    /**
     * Set the font file to be used for drawing the challenge characters
     *
     * @param string $font_file The full path to font file (*.ttf)
     *
     * @return void
     */
    public function setImageFont($font_file)
    {
        // validate
        if (! @file_exists($font_file)
            || ! @is_readable($font_file)
        ) {
            throw new Exception('setImageFont: invalid parameters');
        }

        // store given value
        $this->_imageFont = $font_file;
    }

    /**
     * Generate new challenge equation
     * Called implicitly in constructor
     *
     * @param void
     * @return void
     */
    public function newChallenge()
    {
        // allowed operations
        static $operations = array(
            '+', '-', '*'
        );

        // decide which operation
        $operation = $operations[rand(0, count($operations) - 1)];

        // get some operands
        $operand1 = rand(3, 99);
        $operand2 = rand(1, $operand1 - 1);

        // glue the equation
        $equation = "$operand1 $operation $operand2";
        $result = eval($equation . ';');

        // add equal sign to displayed challenge
        $equation .= ' =';

        // store all of these generated data
        $this->_equation = $equation;
        $this->_result = $result;
    }

    /**
     * Validate the entered result
     *
     * @param int $result The result entered by the user
     *
     * @return boolean Whether the result is correct
     */
    public function isCorrectResult($result)
    {
        return $result === $this->_result;
    }

    /**
     * Get the challenge image for output or conversion to text
     *
     * @param void
     *
     * @return binary $image_data The raw JPEG data
     */
    public function getImage()
    {
        // prepare canvas and colors
        $canvas = $this->_prepareCanvas();

        // draw the equation
        $this->_drawEquation($canvas);

        // get the drawn image data
        ob_start();
        imagejpeg($canvas);
        $image_data = ob_end_clean($canvas);
        ob_end_clean();

        return $image_data;
    }

    /**
     * Output the challenge image to the browser
     *
     * @param void
     *
     * @return void
     */
    public function outputImage()
    {
        // get the challenge image
        $image_data = $this->getImage();

        // did somebody already send anything to the browser?
        if (headers_sent()) {
            throw new Exception('outputImage: Call before sending data to browser');
        }

        // send content type for the challenge image
        header('Content-Type: image/jpeg');

        // send image data to browser
        echo $image_data;

        // the calling script should avoid sending anything additional
    }

    /**
     * Check if all required PHP extensions are loaded
     *
     * @param void
     *
     * @return void
     */
    protected function _checkExtensions()
    {
        if (! extension_loaded('gd')) {
            throw new Exception('_checkExtensions: GD missing');
        }
        // check for freetype
        $gd_info = gd_info();
        if ($gd_info['FreeType Support'] !== true) {
            throw new Exception('_checkExtensions: GD FreeType support missing');
        }
    }

    /**
     * Prepare the canvas, draw its background
     *
     * @param void
     *
     * @return resource $canvas The prepared canvas
     */
    protected function _prepareCanvas()
    {
        $canvas = imagecreatetruecolor(
            $this->_imageWidth,
            $this->_imageHeight
        );

        // extract HEX color
        $background_rgb = $this->_colorFromHex($this->_imageColorBackground);
        extract($background_rgb, EXTR_PREFIX_ALL, 'back_');

        // assign background color
        $background = imagecolorallocate($canvas, $back_r, $back_g, $back_b);
        imagefill($canvas, 0, 0, $background);

        return $canvas;
    }

    /**
     * Draw the equation into the canvas
     *
     * @param resource $canvas The canvas to draw onto
     *
     * @return void
     */
    protected function _drawEquation($canvas)
    {
        // extract HEX color
        $foreground_rgb = $this->_colorFromHex($this->_imageColorBackground);
        extract($foreground_rgb, EXTR_PREFIX_ALL, 'fore_');

        // assign foreground color
        $foreground = imagecolorallocate($canvas, $fore_r, $fore_g, $fore_b);

        // starting size
        $font_size = 20;
        $fits_into_canvas = false;

        while (! $fits_into_canvas) {
            // find out equation dimensions
            $dimensions = imagettfbbox($font_size, 0, $this->_imageFont, $this->_equation);
            $equation_width = $dimensions[2] - $dimensions[0];
            $equation_height = $dimensions[3] - $dimensions[5];

            echo $equation_width;
            die();
        }

        // draw equation
    }
}

?>
