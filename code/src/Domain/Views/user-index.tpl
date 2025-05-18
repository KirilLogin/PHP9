

<p>current_user_is_admin: {{ current_user_is_admin ? 'Да' : 'Нет' }}</p>
"stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

{% block content %}
<p>Текущий пользователь админ? 
    {% if current_user_is_admin %}
        Да
    {% else %}
        Нет
    {% endif %}
</p>
<div class="container mt-4">
    <h2 class="mb-4">{{ title }}</h2>
    {% if current_user_is_admin %}
    <div class="mb-4">
        <a href="/user/edit/" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Добавить пользователя
        </a>
    </div>
    {% endif %}
    <p>current_user_is_admin = {{ current_user_is_admin ? 'true' : 'false' }}</p>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Фамилия</th>
                            <th>Логин</th>
                            <th>День рождения</th>
                            <th>Роль</th>
                            {% if current_user_is_admin %}
                            <th class="text-end">Действия</th>
                            {% endif %}
                        </tr>
                    </thead>
                    <tbody>
                        {% if users|length > 0 %}
                            {% for user in users %}
                            <tr id="user-row-{{ user.getUserId() }}">
                                <td>{{ user.getUserId() }}</td>
                                <td>{{ user.getUserName() }}</td>
                                <td>{{ user.getUserLastName() }}</td>
                                <td>{{ user.getUserLogin() }}</td>
                                <td>
                                    {% if user.getUserBirthday() %}
                                        {{ user.getUserBirthday()|date('d.m.Y') }}
                                    {% else %}
                                        <span class="text-muted">Не указано</span>
                                    {% endif %}
                                </td>
                                <td>
                                    <span class="badge bg-{{ user.getUserRole() == 'admin' ? 'primary' : 'secondary' }}">
                                        {{ user.getUserRole() == 'admin' ? 'Админ' : 'Пользователь' }}
                                    </span>
                                </td>
                                {% if current_user_is_admin %}
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/user/edit/?id={{ user.getUserId() }}" 
                                           class="btn btn-outline-primary"
                                           title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-outline-danger delete-btn" 
                                                data-id="{{ user.getUserId() }}"
                                                title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                {% endif %}
                            </tr>
                            {% endfor %}
                        {% else %}
                            <tr>
                                <td colspan="{{ current_user_is_admin ? '7' : '6' }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-people display-6 d-block mb-2"></i>
                                    Пользователи не найдены
                                </td>
                            </tr>
                        {% endif %}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить этого пользователя? Это действие нельзя отменить.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const deleteModal = new bootstrap.Modal('#confirmDeleteModal');
    let userIdToDelete = null;

    // Delete button click handler
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        userIdToDelete = $(this).data('id');
        deleteModal.show();
    });

    // Confirm delete handler
    $('#confirmDelete').click(function() {
        if (!userIdToDelete) return;
        
        $.ajax({
            url: '/user/delete/',
            type: 'POST',
            data: {
                id: userIdToDelete,
                csrf_token: '{{ csrf_token }}'
            },
            headers: {
                'X-CSRF-Token': '{{ csrf_token }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast('Пользователь успешно удален', 'success');
                    $(`#user-row-${userIdToDelete}`).remove();
                } else {
                    showToast(response.error || 'Ошибка при удалении', 'danger');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.error || 'Ошибка сервера', 'danger');
            },
            complete: function() {
                deleteModal.hide();
                userIdToDelete = null;
            }
        });
    });

    // Toast notification function
    function showToast(message, type) {
        const toastHTML = `
            <div class="toast show align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('.toast-container').append(toastHTML);
        setTimeout(() => {
            $('.toast').toast('hide').on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }, 3000);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const userId = $(this).data('id');
    if (!userId) return;

    if (!confirm('Вы действительно хотите удалить этого пользователя?')) {
        return;
    }

    $.ajax({
        url: '/user/delete/',
        type: 'POST',
        data: { id: userId },
        success: function(response) {
            if (response.success) {
                showToast('Пользователь успешно удален', 'success');
                $(`#user-row-${userId}`).remove();
            } else {
                showToast(response.error || 'Ошибка при удалении', 'danger');
            }
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.error || 'Ошибка сервера', 'danger');
        }
    });
});
</script>
{% endblock %}