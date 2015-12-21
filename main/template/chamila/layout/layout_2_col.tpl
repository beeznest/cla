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
                    {% if _u.logged == 0 %}
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#register_modal" aria-haspopup="true" aria-expanded="false">
                                  <i class="fa fa-book"></i> ¿Quieres aprender? Registrate
                    </button>
                    {% include template ~ "/layout/register_form.tpl" %}
                    {% endif %}
                </div>
            </section>
            <section id="focus">
                {% if home_page_block %}
                    {{ home_page_block }}
                {% endif %}
                
            </section>        
            {% endif %}
{% if section_name != 'section-userportal' %}           
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
                <section id="extra-focus" class="text-center" style="padding-bottom: 30px;">
                    <div class="container">
                        <a href="{{ _p.web_main }}auth/courses.php" class="btn btn-primary"><i class="fa fa-book"></i> Ver más cursos en Chamila</a>
                    </div>
                </section>        
{% endif %}

{% if section_name == 'section-userportal' %}
    
<div class="container">
    <div class="section-bread">{{ breadcrumb }}</div>
</div>
    
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
            <h3 class="title-n3">{{ 'BaseCourses' | get_lang }} </h3>
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
            {% if _u.status == 5 %}
                <h3 class="title-n3">{{ 'AdminCourses' | get_lang }} </h3>
                {% else %}
                <h3 class="title-n3">{{ 'SessionCourses' | get_lang }} </h3>
            {% endif %}
            {% endif %}
            
            {{ content }}
        </div>
    </div>
    </section>
{% endif %}
</div>
{% endblock %}
