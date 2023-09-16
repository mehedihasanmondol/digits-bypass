(function (blocks, element) {
    var el = element.createElement;

    blocks.registerBlockType('digits/login-register', {
        edit: function () {
            return el('div', {class: 'digits_sc_block_wrap_editor components-placeholder'}, 'Digits Login/Registration Form');
        },
    });
})(window.wp.blocks, window.wp.element);
