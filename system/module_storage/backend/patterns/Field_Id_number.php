<?php

##################################################################
### Copyright © 2017—2022 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

class Field_Id_number extends Field_Number {

    public $title = 'ID';
    public $attributes = ['data-type' => 'id_number'];
    public $element_attributes = [
        'type'     => 'number',
        'name'     => 'id',
        'required' => true,
        'min'      => 1,
        'max'      => self::INPUT_MAX_NUMBER,
        'step'     => 1
    ];

}
