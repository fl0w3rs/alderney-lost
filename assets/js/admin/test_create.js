$(() => {
    $('#test-create-button').on('click', () => {
        $('#test-create-button').prop('disabled', true)
        axios.post('/api/admin/test/create', $('#test-create-form').customSerialize()).then((response) => {

            if(response.data.status == 'success') {
                // toast.success('Тест успешно создан')
                location.href = base_link + '/admin/test/list'
            } else if(response.data.status == 'error') {
                toast.error(response.data.error.message)
                $('#test-create-button').prop('disabled', false)
            }
        })
    })
})