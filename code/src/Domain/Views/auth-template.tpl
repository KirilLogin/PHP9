{% if not user_authorized %}
    <div class="col-md-3 text-end">
        <a href="/user/login/" class="btn btn-primary">Войти</a>
    </div>
{% else %}
    <p>Добро пожаловать на сайт!</p>
    <p>Admin? {{ current_user_is_admin ? 'Да' : 'Нет' }}</p>
{% endif %}