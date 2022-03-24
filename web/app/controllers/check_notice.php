<?php

die(json_encode(UOJNotice::fetch(UOJTime::str2time(UOJRequest::post('last_time')))));
