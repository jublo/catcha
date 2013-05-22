catcha
======
*Catch-a spammer with a CAPTCHA.*

Copyright (C) 2013 J.M. <me@mynetx.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.


Please enable the GD extension in your PHP environment.

Getting started
---------------

1. Generate a challenge

```
require_once 'catcha.php';

$catcha = new Catcha;
$catcha->outputImage();
```

2. Store the Catcha object

```
$_SESSION['catcha'] = $catcha;
```

3. After the user enters the solution, restore the object

```
$catcha = $_SESSION['catcha'];
```

4. Check if the entered result was correct

```
$entered_result = -1;
if (isset($_POST['your_field_name'])) {
    $entered_result = intval($_POST['your_field_name']);
}
$catcha_solved = $catcha->isCorrectResult($entered_result);
```

Adapting to your needs
----------------------

### Set a custom challenge canvas image size

```
$catcha->setImageSize($desired_width, $desired_height);
```

### Use a custom font

```
$catcha->setImageFont($path_to_font);
```

### Paint the canvas in a different color

```
$catcha->setImageColorBackground('#ffff00');
```

### Write the equation in a different color

```
$catcha->setImageColorForeground('#ff0000');
```

### Generate a new challenge and discard the old one

```
$catcha->newChallenge();
```

### Get raw image data without sending to browser

```
$image_data = $catcha->getImage();
```

