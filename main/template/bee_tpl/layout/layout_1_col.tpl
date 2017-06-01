{% extends template ~ "/layout/page.tpl" %}

{% block body %}
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-md-12">
                {% include template ~ "/layout/page_body.tpl" %}
                {% block content %}
                    {% if content is not null %}
                        <section id="main_content">
                        {{ content }}
                        </section>
                    {% endif %}
                {% endblock %}
                &nbsp;
            </div>
        </div>
    </div>
{% endblock %}
