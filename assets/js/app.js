/**
 * app.js
 * 
 *  
 */

var $ = jQuery;
var debug; //will be overwritten by ajax request
var compression_ajax;
var inline_style
var version;
var minified_jscss;
var site_url;
var script_url;
var msgbox_labels;

var _ajaxurl = (typeof (vjwpad_global) != 'undefined') ? vjwpad_global.ajaxurl : ajaxurl;

/**
 * initialize first by getting Variables via ajax, then followed by calling callback function
 * @param {*} _callback 
 */
function init(_callback) {
    getVariables(_callback);
}

/**
 * check val if it's empty
 * @param {*} val 
 */
function isEmpty( val ) {
    // test results
    //---------------
    // []        true, empty array
    // {}        true, empty object
    // null      true
    // undefined true
    // ""        true, empty string
    // ''        true, empty string
    // 0         false, number
    // true      false, boolean
    // false     false, boolean
    // Date      false
    // function  false
    if (val === undefined) {
        return true; }
    if (typeof (val) == 'function' || typeof (val) == 'number' || typeof (val) == 'boolean' || Object.prototype.toString.call(val) === '[object Date]') {
        return false; }
    if (val === null || val.length === 0) { // null or 0 length array
        return true; }
    if (typeof (val) == "object") {
        var r = true;
        for (var f in val) {
            r = false; }
        return r;
    }
    return false;
}   

var _console = console;
var appConsole = {};
/**
 * override appConsole with debug-switch
 * @param {*} msg 
 * @param {*} obj 
 */
appConsole.log = function(msg, obj) {
    if (debug == 1 && obj !== undefined) {   
        _console.log(msg, obj);
    } else if (debug == 1) {
       _console.log(msg);
    }
};
appConsole.info = function(msg) { _console.info(msg); };
appConsole.error = function(msg, obj) { 
    try {
        _console.error(msg, obj);
    } catch(e) {
    }
};
window.console = console;

/**
 * convert string to boolean
 * @param {*} string 
 */
function stringToBoolean(str){
    switch(str.toString().toLowerCase().trim()){
        case "true": case "yes": case "1": return true;
        case "false": case "no": case "0": case null: return false;
        default: return false;
    }
}

/**
 * returns true if obj isJSON, otherwise false
 * @param {*} obj 
 */
function isJSON(obj) {
    try {
        JSON.parse(obj);
    } catch(e) {
        return false;
    }
    return true;
}

/**
 * 
 * @param {*} str 
 */
function getSlug(str) {
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();
    // remove accents, swap ñ for n, etc
    var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
    var to   = "aaaaaeeeeeiiiiooooouuuunc------";
    for (var i=0, l=from.length ; i<l ; i++) {
        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }
    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
    .replace(/\s+/g, '-') // collapse whitespace and replace by -
    .replace(/-+/g, '-'); // collapse dashes
    return str;            
}

/**
 * 
 * @param {*} str 
 */
function stripslashes (str) {
    return (str + '')
    .replace(/\\(.?)/g, function (s, n1) {
      switch (n1) {
        case '\\':
          return '\\'
        case '0':
          return '\u0000'
        case '':
          return ''
        default:
          return n1
      }
    })
}

/**
 * convertURL Function for tinymce
 * @param {*} url 
 * @param {*} node 
 * @param {*} on_save 
 * @param {*} name 
 */
function internalURLConverter(url, node, on_save, name) {
    url = url.replace(/\\/g, "");
    url = url.replace('https://','//');
    url = url.replace('http://','//');
    appConsole.log('url',url);

    return url;
}

/**
 * Open url in new Tab
 * @param {*} url 
 */
function openInNewTab(url) {
    var win = window.open(url, '_blank');
    if (win) {
        win.focus();        
    } else {
        //allow popups
        alert('Please allow Popups!');
    }
}

function copyToClipboard(elem) {
    var copyText = document.getElementById(elem);
    copyText.select();
    copyText.setSelectionRange(0, 99999); //For mobile devices
    document.execCommand("copy");
    return copyText.value;
}

/**
 * 
 * $.unserialize
 *
 * Takes a string in format "param1=value1&param2=value2" and returns an object { param1: 'value1', param2: 'value2' }. If the "param1" ends with "[]" the param is treated as an array.
 *
 * Example:
 *
 * Input:  param1=value1&param2=value2
 * Return: { param1 : value1, param2: value2 }
 *
 * Input:  param1[]=value1&param1[]=value2
 * Return: { param1: [ value1, value2 ] }
 *
 * note Support params like "param1[name]=value1" (should return { param1: { name: value1 } })
 */
(function($){
	$.unserialize = function(serializedString){
		var str = decodeURI(serializedString);
		var pairs = str.split('&');
		var obj = {}, p, idx, val;
		for (var i=0, n=pairs.length; i < n; i++) {
			p = pairs[i].split('=');
			idx = p[0];

			if (idx.indexOf("[]") == (idx.length - 2)) {
				// Eh um vetor
				var ind = idx.substring(0, idx.length-2)
				if (obj[ind] === undefined) {
					obj[ind] = [];
				}
				obj[ind].push(p[1]);
			}
			else {
				obj[idx] = p[1];
			}
		}
		return obj;
	};
})(jQuery);
