document.addEventListener('DOMContentLoaded', function() {
            var history = document.querySelector('#larkon-chat-container-unique .larkon-chat-history');
            if (history) history.scrollTop = history.scrollHeight;

            var form = document.querySelector('.larkon-chat-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    fetch(larkon_vars.ajax_url, {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                history.insertAdjacentHTML('beforeend', d.data.html);
                                history.scrollTop = history.scrollHeight;
                                this.reset();
                            } else {
                                alert(d.data.message || 'Error');
                            }
                        });
                });
            }
        });