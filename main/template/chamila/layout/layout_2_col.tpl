{% extends template ~ "/layout/main.tpl" %}

{% block body %}
	<div class="page-home">
            {% if section_name == 'section-mycampus' and hide_special_search_block == 0 %}
            <section id="hero">
                <div class="container">
                    <div class="badge-big"><img src="{{ _p.web_css_theme }}images/badge-big.png"></div>
                    <h1 class="title-n1">¿Qué te gustaría aprender?</h1>
                    <form id="search-course" class="form-horizontal">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" id="s" placeholder="Escribe y busca el curso que buscas...">
                                <div class="input-group-addon"><i class="glyphicon glyphicon-search"></i></div>
                            </div>
                        </div>
                    </form>
                    <p class="description">Apúntate a nuestros cursos online y evoluciona en tus conocimientos #AtreveteHoy</p>
                    <a class="btn btn-primary"><i class="fa fa-book"></i> ¿Quieres enseñar? Registrate</a>
                </div>
            </section>
            <section id="focus">
                <div class="container">
                <h2>Descubre cursos enseñados por expertos</h2>
                <p>Ofrecemos contenidos premium preparado por profesionales apasionados. Queremos asegurarnos
que tengas la mejor experiencia de aprendizaje.</p>
                </div>
            </section>        
            {% endif %}
        {% if home_page_block %}
            <section id="homepage-home">
                {{ home_page_block }}
            </section>
        {% endif %}
        
        {{ sniff_notification }}

        {% block page_body %}
        {% include template ~ "/layout/page_body.tpl" %}
        {% endblock %}

        {% if welcome_to_course_block %}
            <section id="homepage-course">
            {{ welcome_to_course_block }}
            </section>
        {% endif %}

        {% block content %}
        {% if content is not null %}
            <section id="page-content" class="{{ course_history_page }}">
                {{ content }}
            </section>
        {% endif %}
        {% endblock %}

        {% if announcements_block %}
            <section id="homepage-announcements">
            {{ announcements_block }}
            </section>
        {% endif %}

        {% if course_category_block %}
            <section id="homepage-course-category">
                {{ course_category_block }}
            </section>
        {% endif %}
        <section id="courses">
            <div class="container">
                {% include template ~ "/layout/hot_courses.tpl" %}
            </div>
        </section>
	

	</div>

{% endblock %}
