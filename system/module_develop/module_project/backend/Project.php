<?php

##################################################################
### Copyright © 2017—2022 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

abstract class Project {

    static function select($id) {
        return (new Instance('project', [
            'id' => $id
        ]))->select();
    }

}
