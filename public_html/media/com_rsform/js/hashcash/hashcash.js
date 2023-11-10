window.addEventListener('DOMContentLoaded', function(){
    var buttons = document.querySelectorAll('[data-rsfp-hashcash]');
    if (buttons.length > 0) {
        for (var i = 0; i < buttons.length; i++) {
            var button = buttons[i];
            (function (button) {
                button.addEventListener('click', function(){
                    var iterations = Math.pow(100, parseInt(this.getAttribute('data-hashcash-level')));
                    var text = this.getAttribute('data-hashcash-text');
                    var name = this.getAttribute('data-hashcash-name');
                    var count = 0;
                    var pattern = new RegExp('^0{' + parseInt(this.getAttribute('data-hashcash-level')) + '}');
                    var container = this.querySelector('.hashcash');

                    if (!container)
                    {
                        return;
                    }

                    // Prevent multiple clicks
                    if (container.classList.contains('hashcash__working') || container.classList.contains('hashcash__done'))
                    {
                        return;
                    }

                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    this.parentNode.appendChild(input);

                    container.classList.remove('hashcash__pending');
                    container.classList.add('hashcash__working');

                    window.setTimeout(function(){
                        while (iterations > 0) {
                            var hash = CryptoJS.SHA256(text + count).toString(CryptoJS.enc.Hex);
                            if (hash.match(pattern)) {
                                input.value = count;
                                container.classList.remove('hashcash__working');
                                container.classList.add('hashcash__done');
                                break;
                            }
                            count++;
                            iterations--;
                        }
                    }, 300);
                });
            }(button));
        }
    }
});