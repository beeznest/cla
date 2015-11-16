{% extends template ~ "/layout/main.tpl" %}

{% block body %}
<div class="page-home">
    <section id="content-body">
        <div class="container">
            {% include template ~ "/layout/page_body.tpl" %}
            {% block content %}
            {% if content is not null %}
                {{ content }}
            {% endif %}
            {% endblock %}
        </div>
    </section>
</div>
{% endblock %}
