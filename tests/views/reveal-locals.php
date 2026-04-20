<?php
echo ($_view instanceof \Nullai\Vista\View) ? 'VIEW_OK' : 'VIEW_CLOBBERED';
echo '|';
echo is_array($_data) ? 'DATA_OK' : 'DATA_CLOBBERED';
echo '|';
echo ($_parent_view instanceof \Nullai\Vista\View) ? 'PARENT_VIEW_OK' : 'PARENT_VIEW_CLOBBERED';
echo '|';
echo is_array($parent) ? 'PARENT_OK' : 'PARENT_CLOBBERED';
