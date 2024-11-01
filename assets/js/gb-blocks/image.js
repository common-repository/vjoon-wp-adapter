/**
 * 
 * image.js
 * 
 * Extend Gutenberg Image Block
 * 
 */


jQuery(document).ready(function($){ //on Document ready


    wp.domReady( function() {
        init( function() { //initialize vars
            console.log('wp-image block extender loaded...');
            extend_wpimage_block();
        }); 

    } );
}); 

function extend_wpimage_block() {
    appConsole.log('wp-dom ready, extend wp-image block',wp);
    
    wp.hooks.addFilter(
        'blocks.registerBlockType',
        'vj-wp-adapter/attribute/percent',
        addListBlockClassName
    );

    wp.hooks.addFilter(
        'editor.BlockEdit', 
        'vj-wp-adapter/with-percent-control', 
        withPercentClassName        
    )
}

function addListBlockClassName( settings, name ) {
    if ( name !== 'core/image' ) {
        return settings;
    }
 
    return lodash.assign( {}, settings, {
        supports: lodash.assign( {}, settings.supports, {
            className: true
        } ),
    } );
}

const { createHigherOrderComponent } = wp.compose;
const withPercentClassName = createHigherOrderComponent( ( BlockListBlock ) => {
    return ( props ) => {
        if(props.attributes.size) {
            return <BlockListBlock { ...props } className={ "block-" + props.attributes.size } />;
        } else {
            return <BlockListBlock {...props} />
        }

    };
}, 'withPercentClassName' );
