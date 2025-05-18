

{% block content %}
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">Редактирование пользователя</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="/user/update/" novalidate>
                        <input type="hidden" name="csrf_token" value="{{ csrf_token }}">
                        <input type="hidden" name="id" value="{{ user.getUserId() }}">

                        <div class="mb-3">
                            <label for="name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ user.getUserName() }}" required>
                            <div class="invalid-feedback">Пожалуйста, укажите имя</div>
                        </div>

                        <div class="mb-3">
                            <label for="lastname" class="form-label">Фамилия</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" 
                                   value="{{ user.getUserLastName() }}" required>
                            <div class="invalid-feedback">Пожалуйста, укажите фамилию</div>
                        </div>

                        <div class="mb-3">
                            <label for="birthday" class="form-label">День рождения</label>
                            <input type="text" class="form-control" id="birthday" name="birthday"
                                   placeholder="ДД-ММ-ГГГГ"
                                   value="{% if user.getUserBirthday() %}{{ user.getUserBirthday()|date('d-m-Y') }}{% endif %}"
                                   required>
                            <div class="invalid-feedback">Формат: ДД-ММ-ГГГГ</div>
                        </div>

                        {% if current_user_is_admin %}
                        <div class="mb-3">
                            <label for="role" class="form-label">Роль</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" {% if user.getUserRole() == 'user' %}selected{% endif %}>Пользователь</option>
                                <option value="admin" {% if user.getUserRole() == 'admin' %}selected{% endif %}>Администратор</option>
                            </select>
                        </div>
                        {% endif %}

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/user/index/" class="btn btn-secondary me-md-2">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});
</script>
{% endblock %}