<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

<div id="buy-courses-tabs">
        <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
            <li id="buy-courses-tab" class="{{ showing_courses ? 'active' : '' }}" role="presentation">
                <a href="course_catalog.php" aria-controls="buy-courses" role="tab">{{ 'Courses'|get_lang }}</a>
            </li>
            {% if sessions_are_included %}
                <li id="buy-sessions-tab" class="{{ showing_sessions ? 'active' : '' }}" role="presentation">
                    <a href="session_catalog.php" aria-controls="buy-sessions" role="tab">{{ 'Sessions'|get_lang }}</a>
                </li>
            {% endif %}
            {% if services_are_included %}
                <li id="buy-sessions-tab" class="{{ showing_services ? 'active' : '' }}" role="presentation">
                    <a href="service_catalog.php" aria-controls="buy-services" role="tab">{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                </li>
            {% endif %}
        </ul>

    <div class="tab-content">
        <div class="tab-pane active" aria-labelledby="buy-sessions-tab" role="tabpanel">
            <div class="row">
                <div class="col-md-3">
                    {{ search_filter_form }}
                </div>
                <div class="col-md-9">
                    <div class="row">
                        {% if showing_courses %}
                            {% for course in courses %}
                                <div class="col-md-4 col-sm-6">
                                    <article class="thumbnail">
                                        <img alt="{{ course.title }}" class="img-responsive" src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                                        <div class="caption">
                                            {% set course_description_url = _p.web_ajax ~ 'course_home.ajax.php?' ~ {'code': course.code, 'a': 'show_course_information'}|url_encode() %}
                                            <h3>
                                                <a class="ajax" href="{{ course_description_url }}" data-title="{{ course.title }}">{{ course.title }}</a>
                                            </h3>
                                            <ul class="list-unstyled">
                                                {% for teacher in course.teachers %}
                                                    <li><em class="fa fa-user"></em> {{ teacher }}</li>
                                                    {% endfor %}
                                            </ul>
                                            <p class="lead text-right">{{ course.currency }} {{ course.price }}</p>
                                            {% if course.enrolled == "YES" %}
                                                <div class="alert alert-success">
                                                    <em class="fa fa-check-square-o fa-fw"></em> {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                                                </div>
                                            {% elseif course.enrolled == "NO" %}
                                                <div class="text-center">
                                                    <a class="ajax btn btn-primary" title="" href="{{ course_description_url }}" data-title="{{ course.title }}">
                                                        <em class="fa fa-file-text"></em> {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                    <a class="btn btn-success" title="" href="{{ _p.web_plugin ~ 'buycourses/src/process.php?' ~ {'i': course.id, 't': 1}|url_encode() }}">
                                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                </div>
                                            {% elseif course.enrolled == "TMP" %}
                                                <div class="alert alert-info">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                            {% endif %}
                                        </div>
                                    </article>
                                </div>
                            {% endfor %}
                        {% endif %}

                        {% if showing_sessions %}
                            {% for session in sessions %}
                                <div class="col-md-4 col-sm-6">
                                    <article class="thumbnail">
                                        <img alt="{{ session.name }}" class="img-responsive" src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                                        <div class="caption">
                                            <h3>
                                                <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}">{{ session.name }}</a>
                                            </h3>
                                            {% if 'show_session_coach'|get_setting == 'true' %}
                                                <p><em class="fa fa-user fa-fw"></em> {{ session.coach }}</p>
                                            {% endif %}
                                            <p><em class="fa fa-calendar fa-fw"></em> {{ session.dates.display }}</p>
                                            <p class="lead text-right">{{ session.currency }} {{ session.price }}</p>
                                            <ul class="list-unstyled">
                                                {% for course in session.courses %}
                                                    <li>
                                                        <em class="fa fa-book fa-fw"></em> {{ course.title }}
                                                        {% if course.coaches|length %}
                                                            <ul>
                                                                {% for coach in course.coaches %}
                                                                    <li>{{ coach }}</li>
                                                                    {% endfor %}
                                                            </ul>
                                                        {% endif %}
                                                    </li>
                                                {% endfor %}
                                            </ul>
                                            {% if session.enrolled == "YES" %}
                                                <div class="alert alert-success">
                                                    <em class="fa fa-check-square-o fa-fw"></em> {{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}
                                                </div>
                                            {% elseif session.enrolled == "NO" %}
                                                <div class="text-center">
                                                    <a class="btn btn-success" href="{{ _p.web_plugin ~ 'buycourses/src/process.php?' ~ {'i': session.id, 't': 2}|url_encode() }}">
                                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                </div>
                                            {% elseif session.enrolled == "TMP" %}
                                                <div class="alert alert-info">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                            {% endif %}
                                        </div>
                                    </article>
                                </div>
                            {% endfor %}
                        {% endif %}
                            
                        {% if showing_services %}
                            {% for service in services %}
                                <div class="col-md-4 col-sm-6">
                                    <article class="thumbnail">
                                        {% if service.applies_to == 0 %}
                                            <img alt="{{ service.name }}" class="img-responsive" src="{{ 'session_default.png'|icon() }}">
                                        {% elseif service.applies_to == 1 %}
                                            <img alt="{{ service.name }}" class="img-responsive" style="margin: auto;" src="{{ _p.web }}plugin/buycourses/resources/img/bc-user.png">
                                        {% elseif service.applies_to == 2 %}
                                            <img alt="{{ service.name }}" class="img-responsive" style="margin: auto;" src="{{ _p.web }}plugin/buycourses/resources/img/bc-course.png">
                                        {% elseif service.applies_to == 3 %}
                                            <img alt="{{ service.name }}" class="img-responsive" style="margin: auto;" src="{{ _p.web }}plugin/buycourses/resources/img/bc-session.png">
                                        {% endif %}
                                        <div class="caption">
                                            <h3>
                                                {{ service.name }}
                                            </h3>
                                            <ul class="list-unstyled">
                                                {% if service.applies_to == 0 %}
                                                <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'None' | get_lang }}</li>
                                                {% elseif service.applies_to == 1 %}
                                                <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'User' | get_lang }}</li>
                                                {% elseif service.applies_to == 2 %}
                                                <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Course' | get_lang }}</li>
                                                {% elseif service.applies_to == 3 %}
                                                <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Session' | get_lang }}</li>
                                                {% endif %}
                                                <li><em class="fa fa-clock-o"></em> {{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }} : {{ service.duration_days }} {{ 'Days' | get_lang }}</li>
                                                <li><em class="fa fa-user"></em> {{ service.owner_name }}</li>
                                                <li><em class="fa fa-align-justify"></em> {{ service.description }}</li>
                                            </ul>
                                            <p class="lead text-right">{{ service.currency }} {{ service.price }}</p>
                                            {% if service.enrolled == "YES" %}
                                                <div class="alert alert-success">
                                                    <em class="fa fa-check-square-o fa-fw"></em> {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                                                </div>
                                            {% elseif service.enrolled == "NO" %}
                                                <div class="text-center">
                                                    <a class="btn btn-success" title="" href="{{ _p.web_plugin ~ 'buycourses/src/service_process.php?' ~ {'i': service.id, 't': service.applies_to}|url_encode() }}">
                                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                                    </a>
                                                </div>
                                            {% elseif service.enrolled == "TMP" %}
                                                <div class="alert alert-info">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                            {% endif %}
                                        </div>
                                    </article>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
