
<div id="about-session">
    {%if session.display != '' %}
    <div class="date-session">
        <p><em class="fa fa-clock-o"></em> <em>{{ session_date.display }}</em></p>
    </div>
    {% endif %}
    {% if is_subscribed %}
        <div class="alert alert-info">
            {{ 'AlreadyRegisteredToSession'|get_lang }}
        </div>
    {% endif %}

    {% for course_data in courses %}
        {% set course_video = '' %}

        {% for extra_field in course_data.extra_fields %}
            {% if extra_field.value.getField().getVariable() == 'video_url' %}
                {% set course_video = extra_field.value.getValue() %}
            {% endif %}
        {% endfor %}

        <div class="row">
            
            <div class="col-md-8">
                {% if courses|length > 1 %}
                <div class="course-title">
                    <h3 class="title">{{ course_data.course.getTitle }}</h3>
                </div>
                {% endif %}
                {% if course_video %}
                <div class="course-video">
                    <div class="embed-responsive embed-responsive-16by9">
                        {{ essence.replace(course_video) }}
                    </div>
                </div>
                {% else %}
                <div class="course-image">
                    <img src="{{ course_data.image }}" class="img-responsive"> 
                </div>
                {% endif %}
                <div class="course-panel">
                    {% if course_data.description != '' %}
                    <div class="course-description">
                        <h3 class="title-info"><i class="fa fa-info-circle"></i> {{ "CourseInformation"|get_lang }}</h3>
                        {{ course_data.description.getContent }}
                    </div>
                    {% endif %}
                    {% if course_data.objectives %}
                        <div class="course-objetive">
                            <h3 class="title-info"><em class="fa fa-book"></em> {{ "Objectives"|get_lang }}</h3>
                            <div class="content-info">
                                {{ course_data.objectives.getContent }}
                            </div>
                        </div>
                    {% endif %}
                    {% if course_data.topics %}
                        <div class="course-topics">
                            <h3 class="title-info"><em class="fa fa-book"></em> {{ "Topics"|get_lang }}</h3>
                            <div class="content-info">
                                {{ course_data.topics.getContent }}
                            </div>
                        </div>
                    {% endif %}
                </div>
            </div>
            <div class="col-md-4">
                <div class="sidebar">
                    
                    {% if course_data.coaches %}
                    <div class="panel panel-default teachers">
                        <div class="panel-heading">
                            {{ "Coaches"|get_lang }}
                        </div>
                        <div class="panel-body">
                            
                            {% for coach in course_data.coaches %}
                                    
                                    <div class="coaches-info">
                                        <div class="avatar">
                                            <img class="img-circle" src="{{ coach.image }}" alt="{{ coach.complete_name }}">
                                        </div>
                                        <div class="extra-field">
                                        <h4 class="coaches-title">{{ coach.complete_name }}</h4>

                                        {% for extra_field in coach.extra_fields %}
                                            <dl>
                                                <dt>{{ extra_field.value.getField().getDisplayText() }}</dt>
                                                <dd>{{ extra_field.value.getValue() }}</dd>
                                            </dl>
                                        {% endfor %}
                                        </div>
                                   </div>
                               
                            {% endfor %}
                        </div>
                    </div>
                {% endif %}
                
                
                {% if course_data.tags %}
                    <div class="panel panel-default">
                        <div class="panel-heading">{{ 'Tags'|get_lang }}</div>
                        <div class="panel-body">
                            <ul class="list-inline">
                                {% for tag in course_data.tags %}
                                    <li>
                                        <span class="label label-info">{{ tag.getTag }}</span>
                                    </li>
                                {% endfor %}
                            </ul>
                        </div>
                    </div>
                {% endif %}
                
                
                <!-- social -->
                
                <div class="panel panel-default social-share">
                    <div class="panel-heading">{{ "ShareWithYourFriends"|get_lang }}</div>
                    <div class="panel-body">
                        <div class="icons-social text-center">
                            <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                                <em class="fa fa-facebook fa-2x"></em>
                            </a>
                            <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ pageUrl}|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                                <em class="fa fa-twitter fa-2x"></em>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': session.getName() }|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                                <em class="fa fa-linkedin fa-2x"></em>
                            </a>
                        </div>
                    </div>
                </div>
                
                </div>
            </div>           

           
        </div>

    {% endfor %}

    <div class="row suscriber">
        <div class="col-md-4 col-md-offset-4">
            {% if _u.logged and not is_subscribed %}
                <div class="text-center">
                    {{ subscribe_button }}
                </div>
            {% elseif not _u.logged %}
                {% if 'allow_registration'|get_setting == 'true' %}
                    <a href="{{ _p.web_main ~ 'auth/inscription.php' }}" class="btn btn-info btn-lg">
                        <em class="fa fa-sign-in fa-fw"></em> {{ 'SignUp'|get_lang }}
                    </a>
                {% endif %}
            {% endif %}
        </div>
    </div>
</div>
