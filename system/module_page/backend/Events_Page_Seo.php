<?php

##################################################################
### Copyright © 2017—2022 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore\modules\page;

use effcore\Url;

abstract class Events_Page_Seo {

    static function on_redirect($event, $page) {
        $type = $page->args_get('type');
        if ($type === null) Url::go($page->args_get('base').'/meta');
    }

}
