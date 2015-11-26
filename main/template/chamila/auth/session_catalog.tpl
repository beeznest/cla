{% extends template ~ "/layout/main.tpl" %}

{% block body %}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>

    <div class="container">
        <div class="section-header">
            <h2 class="title-n2">{{ 'Courses'|get_lang }}</h2>
            <h4>Todos nuestros cursos</h4>
        </div>
    </div>

    <section id="session-list">
        <div class="container">
            <div class="row">
                {% for session in sessions %}   
                    <div class="col-xs-12 col-sm-4 col-md-3">
                        <div class="item-session" id="items-session-{{ session.id }}">   
                            <div class="thumbnail">
                                <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" title="{{ session.name }}"><img src="{{ session.image ? _p.web_upload ~ session.image : _p.web_img ~ 'session_default.png' }}"></a>
                                <span class="course-metadata">
                                    <span class="category">{{ session.category_name }}</span>
                                </span>
                            </div>
                            <div class="description">
                                <div class="title">
                                    <h3>
                                        <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" title="{{ session.name }}">
                                            {{ session.name }}
                                        </a>
                                    </h3>
                                </div>
                                <div class="teacher">
                                    <em class="fa fa-graduation-cap"></em>
                                    {{ session.coach_name }}
                                </div>
                                <div class="text">
                                    {{ session.description }}
                                </div>
                                <div class="info">
                                    <div class="col-xs-6 col-md-6">
                                        <i class="fa fa-user"></i> {{ session.nbr_users }}
                                    </div>
                                    <div class="col-xs-6 col-md-6">
                                        <i class="fa fa-book"></i> {{ session.lessons }}
                                    </div>
                                </div>
                                 <div class="options">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                {% else %}
                    <div class="col-xs-12">
                        <div class="alert alert-warning">
                            {{ 'NoResults'|get_lang }}
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>
    </section>

    {{ catalog_pagination }}

{% endblock %}
