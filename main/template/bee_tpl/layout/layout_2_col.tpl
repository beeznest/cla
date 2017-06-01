{% extends template ~ "/layout/page.tpl" %}

{% block body %}
<div class="page-home">
    {% if section_name == 'section-mycampus' and hide_special_search_block == 0 %}
        <section id="hero">
            <div class="container">
                <div class="badge-big"><img src="{{ _p.web_css_theme }}images/badge-big.png"></div>
                <h1 class="title-n1">¿Qué te gustaría aprender?</h1>
                <form id="search-course" class="form-horizontal" action="{{ _p.web_main }}auth/courses.php" method="GET">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" class="form-control" id="s" name="search" placeholder="Escribe y busca el curso que buscas...">
                            <div class="input-group-addon"><i class="glyphicon glyphicon-search"></i></div>
                        </div>
                    </div>
                </form>
                <p class="description">Apúntate a nuestros cursos online y evoluciona en tus conocimientos #AtreveteHoy</p>
                {% if _u.logged == 0 %}
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#register_modal" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-book"></i> ¿Quieres aprender? Registrate
                </button>

                {% endif %}
            </div>
        </section>
        <section id="focus">
            <div class="container">
            {% if home_page_block %}
                <article id="homepage-home">
                    {{ home_page_block }}
                </article>
            {% endif %}
            </div>
        </section>
    {% endif %}
</div>
{% if section_name == 'section-mycampus' %}
<section id="extra-focus" class="text-center" style="padding-bottom: 30px;">
    <div class="container">
        <a href="{{ _p.web_main }}auth/courses.php" class="btn btn-primary"><i class="fa fa-book"></i> Ver más cursos en Chamila</a>
    </div>
</section>
<section id="courses-hot">
    <div class="container">
        {% include template ~ "/layout/hot_courses.tpl" %}
    </div>
</section>
{% endif %}
{% if section_name != 'section-mycampus' %}
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar">
                {% include template ~ "/layout/login_form.tpl" %}
                {% if _u.logged  == 1 %}
                    {{ user_image_block }}
                {% endif %}
                {{ profile_block }}
                {{ course_block }}
                {{ teacher_block }}
                {{ skills_block }}
                {{ certificates_search_block }}
            </div>
        </div>
        <div class="col-md-9">
            <div class="page-content">
                {{ sniff_notification }}
                {% block page_body %}
                    {% include template ~ "/layout/page_body.tpl" %}
                {% endblock %}
                {% if welcome_to_course_block %}
                    <article id="homepage-course">
                    {{ welcome_to_course_block }}
                    </article>
                {% endif %}
                {% block content %}
                    {% if content is not null %}
                        <section id="page" class="{{ course_history_page }}">
                            {{ content }}
                        </section>
                    {% endif %}
                {% endblock %}
                {% if announcements_block %}
                    <article id="homepage-announcements">
                    {{ announcements_block }}
                    </article>
                {% endif %}

            </div>
        </div>
    </div>
</div>
{% endif %}

{% endblock %}
