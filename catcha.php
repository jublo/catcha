<?php

/**
 * Catch-a spammer with a CAPTCHA.
 *
 * @package catcha
 * @version 1.0.1
 * @author Jublo IT Solutions <support@jublo.net>
 * @copyright 2013-2014 Jublo IT Solutions <support@jublo.net>
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
     * Background color for the challenge image
     *
     * @access protected
     */
    protected $_imageColorBackground;

    /**
     * Foreground color for the challenge image
     *
     * @access protected
     */
    protected $_imageColorForeground;

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
        $this->setImageColorBackground('FFF');
        $this->setImageColorForeground('000');

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
     * Set the background color to use in the challenge image
     *
     * @param string $color_back Color to paint the canvas (HEX RGB or RRGGBB)
     *
     * @return void
     */
    public function setImageColorBackground($color_back)
    {
        // validate
        if (! $color_back = $this->_validateColorHex($color_back)) {
            throw new Exception('setImageColorBackground: invalid parameters');
        }

        // store given value
        $this->_imageColorBackground = $color_back;
    }

    /**
     * Set the foreground color to use in the challenge image
     *
     * @param string $color_fore Color to draw the equation (HEX RGB or RRGGBB)
     *
     * @return void
     */
    public function setImageColorForeground($color_fore)
    {
        // validate
        if (! $color_fore = $this->_validateColorHex($color_fore)) {
            throw new Exception('setImageColorForeground: invalid parameters');
        }

        // store given value
        $this->_imageColorForeground = $color_fore;
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
        if ($operation == '*') {
            $operand1 = rand(3, 10);
        } else {
            $operand1 = rand(3, 99);
        }
        $operand2 = rand(1, $operand1 - 1);

        // glue the equation
        $equation = "$operand1 $operation $operand2";
        eval('$result = ' . $equation . ';');

        // use well-known sign for multiplication
        $equation = str_replace('*', html_entity_decode('&times;'), $equation);

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
        return intval($result) == $this->_result;
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
        imagejpeg($canvas, null, 70);
        $image_data = ob_get_contents();
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
     * Validate a given hex color
     *
     * @param string $color_hex The hex color to validate
     *
     * @return (string|FALSE) The validated color
     */
    protected function _validateColorHex($color)
    {
        if (! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
            return false;
        }
        if (substr($color, 0, 1) === '#') {
            $color = substr($color, 1);
        }
        // convert to uppercase
        $color = strtoupper($color);

        // if 3-digit format, expand
        if (strlen($color) == 3) {
            $color =
                str_repeat(substr($color, 0, 1), 2)
                . str_repeat(substr($color, 1, 1), 2)
                . str_repeat(substr($color, 2, 1), 2);
        }
        return $color;
    }

    /**
     * Convert HEX color to R, G, B
     *
     * @param string $color_hex The hex color
     *
     * @return array('r' => int, 'g' => int, 'b' => int) extracted color values
     */
    protected function _colorFromHex($color_hex)
    {
        $red = hexdec(substr($color_hex, 0, 2));
        $green = hexdec(substr($color_hex, 2, 2));
        $blue = hexdec(substr($color_hex, 4, 2));

        return array(
            'r' => $red,
            'g' => $green,
            'b' => $blue
        );
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
        extract($background_rgb, EXTR_PREFIX_ALL, 'back');

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
    protected function _drawEquation(&$canvas)
    {
        // extract HEX color
        $foreground_rgb = $this->_colorFromHex($this->_imageColorForeground);
        extract($foreground_rgb, EXTR_PREFIX_ALL, 'fore');

        // assign foreground color
        $foreground = imagecolorallocate($canvas, $fore_r, $fore_g, $fore_b);

        // starting size
        $font_size = 32;
        $fits_into_canvas = false;

        $canvas_width = intval(.7 * $this->_imageWidth);
        $canvas_height = intval(.5 * $this->_imageHeight);

        while (! $fits_into_canvas) {
            // find out equation dimensions
            $dimensions = imagettfbbox($font_size, 0, $this->_imageFont, $this->_equation);
            $equation_width = $dimensions[2] - $dimensions[0];
            $equation_height = $dimensions[3] - $dimensions[5];

            if ($equation_width > $canvas_width) {
                $font_size--;
            } elseif($equation_height > $canvas_height) {
                $font_size--;
            } else {
                $fits_into_canvas = true;
            }
        }

        // get margins
        $margin_left = rand(2, $this->_imageWidth - $equation_width - 2);
        $margin_top = rand(2, $this->_imageHeight - $equation_height - 2);

        // draw equation
        imagettftext(
            $canvas,
            $font_size,
            0,
            $margin_left,
            $margin_top + $equation_height,
            $foreground,
            $this->_imageFont,
            $this->_equation
        );
    }
}

?>
