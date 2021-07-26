$(() => {
    $('#add-new-option').on('click', () => {
        $('#option-block').append($('#option-template').html())

        document.querySelectorAll('.form-outline').forEach((formOutline) => {
            new mdb.Input(formOutline).init();
        });
    })
    $('#question-edit-button').on('click', () => {
        axios.post('/api/admin/question/' + QUESTION_ID + '/edit', new FormData($('#question-edit-form')[0])).then((response) => {

            if(response.data.status == 'success') {
                toast.success('Вопрос успешно отредактирован')
            } else if(response.data.status == 'error') {
                toast.error(response.data.error.message)
            }
        })
    })
})