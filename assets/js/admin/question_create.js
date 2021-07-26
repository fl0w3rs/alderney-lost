$(() => {
    $('#add-new-option').on('click', () => {
        $('#option-block').append($('#option-template').html())

        document.querySelectorAll('.form-outline').forEach((formOutline) => {
            new mdb.Input(formOutline).init();
        });
    })
    $('#question-create-button').on('click', () => {
        $('#question-create-button').prop('disabled', true)
        axios.post('/api/admin/test/' + TEST_ID + '/question/create', new FormData($('#question-create-form')[0])).then((response) => {
            if(response.data.status == 'success') {
                toast.success('Вопрос успешно создан')
                location.href = base_link + '/admin/test/' + TEST_ID + '/questions'
            } else if(response.data.status == 'error') {
                toast.error(response.data.error.message)
                $('#question-create-button').prop('disabled', false)
            }
        })
    })
})