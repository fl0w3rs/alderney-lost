$(() => {
    var loginButton = $('#lgn-btn');
    loginButton.on('click', async () => {
        loginButton.prop('disabled', true);
        axios.get('/api/access').then((response) => {
            if(response.data.access == true) {
                $('#lgn-btn').fadeOut({complete: () => {
                    $('#lgn-btn').css({visibility: 'hidden', display: 'block'}).slideUp({complete: () => {
                        $('#logo').addClass('blink-animation') 
                    }});
                    
                    setTimeout(() => { location.href = base_link + '/home' }, 2500)
                }})
            } else {
                // toast.error('Ошибка!')
                $('#lgn-btn').fadeOut({complete: () => { 
                    $('#lgn-btn').replaceWith(`<div class="mt-1 h5 text-warning">Вы не участник сообщества или не авторизованы</div>`)
                }})
            }
            // console.log(response); console.log(response.data.admin)
        })
    })
})