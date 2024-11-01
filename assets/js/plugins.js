/**
 * confirm deactivation 
 */

jQuery(document).ready(function($){ //on Document ready
    init( function() { //initialize vars
        overridePluginDeactivation();
        addMorePopUp();
        initMessagebox();
    }); 

    function overridePluginVersion() {
        $('div.plugin-version-author-uri').each( function(elem, val) {
            var content = $(val).html();
            content = content.replace('${VERSION_STRING}', version);
            $(val).html(content);
        
        });
    }

    function addMorePopUp() {
        $(document).on('click','a#build', function(event) {
            event.preventDefault();

            $.post({
                url: _ajaxurl,
                data: { 'action': 'app_ajaxcall', 'operation': 'getBuildInfo' },
                success:function(data) {
                    var jsonData = '';
                    if ( compression_ajax == 1 ) {
                        var lz = atob(data);
                        jsonData = JSON.parse(lz);
                    } else {
                        jsonData = JSON.parse(data);
                    }

                    $.MessageBox({
                        buttonsOrder: 'done',
                        message: jsonData.result,
                    });
                },
                error:function(errorThrown) {
                    appConsole.log('errorThrown',errorThrown);
                    return false;
                }
                
            });

        });
    }

    function overridePluginDeactivation() {
        appConsole.log('load plugins override js...');
		$(document).on('click','.active[data-slug="vjoon-wp-adapter"] a#deactivate-vjoon-wp-adapter', function(event) {
            event.preventDefault();

            $.post({
                url: _ajaxurl,
                data: { 'action': 'app_ajaxcall', 'operation': 'getUserlist' },
                success:function(data) {
                    var jsonData = '';
                    if ( compression_ajax == 1 ) {
                        var lz = atob(data);
                        jsonData = JSON.parse(lz);
                    } else {
                        jsonData = JSON.parse(data);
                    }

                    var users = [];
                    users = jsonData.result;

                    var _data = {
                        deleteContent : {
                            type: 'checkbox',
                            title: msgbox_labels.deleteContentLabel,
                            label : '',

                        },
                        attributeContent: {
                            type: 'checkbox',
                            title: msgbox_labels.attributeContentLabel,
                            label: '',
                            customClass: 'selectUser'
                            
                        },
                        selectUser : {
                            type: 'select',
                            label: '',
                            options: users,
                            defaultValue: '1'
                        }
                    };

                    $.MessageBox({
                        input: _data,
                        speed: 400,
                        width: '50%',
                        message: msgbox_labels.questionLabel,
                        buttonsOrder: 'fail done',
                        buttonFail: msgbox_labels.btnCancel,
                        buttonDone: msgbox_labels.btnOK,
                        filterDone: function(data) {
                            if (!data.attributeContent && !data.deleteContent) {
                                return msgbox_labels.errorMessage;
                            }
                        }
                    }).done(function(data) {
                        var _json = JSON.parse(JSON.stringify(data));
                        appConsole.log(_json,'response');

                        var selectedUser = null;
                        if (_json.attributeContent == true) {
                            selectedUser =  _json.selectUser;
                        }

                        //note: set selectedUser in options
                        $.post({
                            url: _ajaxurl,
                            data: { 'action': 'app_ajaxcall', 'operation': 'assignUser', 'data' : [selectedUser] },
                            success:function(data) {
                                var jsonData = '';
                                if ( compression_ajax == 1 ) {
                                    var lz = atob(data);
                                    jsonData = JSON.parse(lz);
                                } else {
                                    jsonData = JSON.parse(data);
                                }
                                appConsole.log(jsonData,'response');

                                if (jsonData.result == true) {
                                    var urlRedirect = document.querySelector('[data-slug="vjoon-wp-adapter"] a').getAttribute('href'); //url for deactivation
                                    window.location.href = urlRedirect; //redirect to deactivation
                                }
                            },
                            error:function(errorThrown) {
                                appConsole.log('errorThrown',errorThrown);
                                return false;
                            }
                        });

                    }).fail(function(data) {
                        //noting to do
                    });
                    
                    $('select[name="selectUser"]').detach().appendTo( $('input.selectUser').parent() );


                },
                error:function(errorThrown) {
                    appConsole.log('errorThrown',errorThrown);
                    return false;
                }
            });
                

        });
    }

    function initMessagebox() {
        appConsole.log('init Messagebox...');

        $(document).on('click','.messagebox input.messagebox_content_checkbox[name="deleteContent"]', function(data) {
            appConsole.log('delete selected',data);
            $('.messagebox input.messagebox_content_checkbox[name="attributeContent"]').prop('checked', false);

        });
        $(document).on('click','.messagebox input.messagebox_content_checkbox[name="attributeContent"]', function(data) {
            appConsole.log('attribute selected',data);
            $('.messagebox input.messagebox_content_checkbox[name="deleteContent"]').prop('checked', false);

        });
    }

	function check_uninstallable() {
		var plugin = $('[data-slug="vjoon-wp-adapter"]');
		if ( plugin.hasClass('is-uninstallable') && plugin.hasClass('inactive')) {
			plugin.removeClass('inactive').addClass('active');
			plugin.find('a#activate-vjoon-wp-adapter').remove();
			plugin.find('th.check-column').children().remove();
		}
	}

});
