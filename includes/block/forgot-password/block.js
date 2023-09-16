(function (blocks, element) {
    var el = element.createElement;

    blocks.registerBlockType('digits/forgot-password', {
        edit: function () {
            return el('div', {class: 'digits_sc_block_wrap_editor components-placeholder'}, 'Digits Forgot Password Form');
        },
    });
})(window.wp.blocks, window.wp.element);
