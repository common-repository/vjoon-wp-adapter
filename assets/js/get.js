/**
 * after getting variables, call callback function
 * @param {*} _callback 
 */
function getVariables(_callback) {
    $.post({
        url: _ajaxurl,
        data: { 'action': 'app_ajaxcall', 'operation': 'getVars' },
        success:function(data) {
            try {
                var jsonData = '';
                if ( ! isJSON(data) ) { //tryParse JSON, if compression is set to true, but getvars is not received yet
                    var lz = window.atob(data);
                    jsonData = JSON.parse(lz);
                } else {
                    jsonData = JSON.parse(data);
                }
               
                switch (jsonData.return) {
    
                    case 'getVars':
                        debug = jsonData.result.debug;
                        compression_ajax = jsonData.result.compression_ajax;
                        minified_jscss = jsonData.result.minified_jscss;
                        inline_style = jsonData.result.inline_style;
                        msgbox_labels = jsonData.result.msgboxlabels;
 
                        version = jsonData.result.version;
                        site_url = jsonData.result.site_url;
                        script_url = jsonData.result.script_url;
                        appConsole.log('requested getVars ...', jsonData);
                        break;
    
                    default:
                        appConsole.log('notKnownOrImplemented - returned',jsonData);
                        break;
    
                }
    
                appConsole.log('run callback function...',_callback);
                _callback();

            } catch (e) {
                appConsole.log('error ', e)
            }   
        },
        error:function(errorThrown) {
            appConsole.log('errorThrown',errorThrown);
            return false;
        }
    });
}
