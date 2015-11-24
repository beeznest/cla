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
                    <div class="row message">
                        <div class="col-md-12">
                            <h2 class="title-n2">Descubre cursos enseñados por expertos</h2>
                            <p class="description">Ofrecemos contenidos premium preparado por profesionales apasionados. Queremos asegurarnos
que tengas la mejor experiencia de aprendizaje.</p>
                        </div>
                    </div>
                    <div class="row user-tips">
                        <div class="col-sm-4">
                            <i class="fa fa-smile-o fa-4x"></i>
                            <h3 class="title-n3">Aprende a tu ritmo</h3>
                            <p class="description">Disfruta de los cursos desde casa, sin horarios ni entregas. Tú marcas tu propia agenda</p>
                        </div>
                        <div class="col-sm-4">
                            <i class="fa fa-laptop fa-4x"></i>
                            <h3 class="title-n3">En primera fila</h3>
                            <p class="description">Vídeos de máxima calidad para que no pierdas detalle. Y como el acceso es ilimitado, puedes verlos una y otra vez</p>
                        </div>
                        <div class="col-sm-4">
                            <i class="fa fa-thumbs-o-up fa-4x"></i>
                            <h3 class="title-n3">De la mano del profesor</h3>
                            <p class="description">Aprende técnicas y métodos de gran valor explicados por los expertos, respuesta casi inmediata a cualquier duda que tengas.</p>
                        </div>
                    </div>      
                </div>
            </section>        
            {% endif %}
{% if section_name != 'section-mycourses' %}           
        <section id="content-body">
            <div class="container">
                
                    {% block breadcrumb %}
                        {{ breadcrumb }}
                    {% endblock %}
                
                    <div class="notification">
                        {{ sniff_notification }}
                    </div>
                    
                {% if welcome_to_course_block %}
                    {{ welcome_to_course_block }}
                {% endif %}
                {% if home_page_block %}
                    {{ home_page_block }}
                {% endif %}
                {% include template ~ "/session/sessions_current.tpl" %}
                {% block page_body %}
                    {% include template ~ "/layout/page_body.tpl" %}
                {% endblock %}
                {% block content %}
                {% if content is not null %}
                    <div class="{{ course_history_page }}">
                        <div class="row">
                            {{ content }}
                        </div>
                    </div>
                {% endif %}
                {% endblock %}
            </div> 
        </section>
{% endif %}
{% if section_name == 'section-mycourses' %}
    <section id="my-courses" class="container">
        <div class="row">
        <div class="col-md-3">
            <div class="sidebar">
               {{ user_image_block }}
               {{ profile_block }}
               {{ course_block }}
               {{ navigation_course_links }}
               {{ skills_block }}
            </div>
        </div>
        
        <div class="col-md-9">
            
            {% if courseitems %}
            <section id="course-items">
            <div class="row">
                {% for items in courseitems %}
                
                <div class="col-md-3">
                    <div id="courseid-{{ items['id'] }}" class="course-book">
                        <div class="course-icon">
                            <img src="{{ "scorms.png"|icon(64) }}"/>
                        </div>
                        <div class="course-info">
                            <h4 class="title"><a href="{{ items['link'] }}">{{ items['title'] }}</a></h4>
                            <!-- <p class="teacher">{{ items['teachers'] }}</p> -->
                        </div>
                        <div class="edit">
                            <a class="btn btn-default btn-xs" href="{{ items['actions'] }}"><i class="fa fa-pencil"></i> {{ 'Edit' | get_lang }}</a>
                        </div>
                    </div>
                </div>
                
                {% endfor %}
            </div>
            </section>
            {% endif %}
            
            {{ content }}
        </div>
    </div>
    </section>
{% endif %}
</div>
{% endblock %}
