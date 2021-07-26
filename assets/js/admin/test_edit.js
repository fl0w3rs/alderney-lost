$(() => {
    $('#test-edit-button').on('click', () => {
        axios.post('/api/admin/test/' + TEST_ID + '/edit', $('#test-edit-form').customSerialize()).then((response) => {

            if(response.data.status == 'success') {
                toast.success('Тест успешно отредактирован')
            } else if(response.data.status == 'error') {
                toast.error(response.data.error.message)
            }
        })
    })
})