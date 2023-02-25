<?php

##################################################################
### Copyright © 2017—2022 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

trait Trait__Widget_Files_pictures {

    public $thumbnails_is_allowed = true;
    public $thumbnails = [
        'small'  => 'small',
        'middle' => 'middle'];
    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦
    public $picture_default_settings = [
        'title'  => 'click to open in new window',
        'alt'    => 'thumbnail',
        'target' => 'widget_files-pictures-items'
    ];

}
