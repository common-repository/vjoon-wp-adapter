/**
 * debug.js
 */

//debug div late bind
$(document).on('click','div.debug div', function () { //late bind
    $(this).parent().find('pre').toggle();
});
