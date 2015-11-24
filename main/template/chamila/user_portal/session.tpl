{% if not session.show_simple_session_info %}
        <div class="col-md-12">
            <div class="item-session">
                <div class="row session-border-top">
                    <div class="col-md-12">
                        {% if session.show_link_to_session %}
                            {% if session.courses|length == 1 %}
                                {% set course = session.courses|first %}
                                <div class="title">
                                    <h3>
                                        <a href="{{ course.link }}" alt="{{ session.title }}" title="{{ session.title }}" style="text-decoration: none">{{ session.title }}</a>
                                        <a class="pull-right session-info" alt="{{ session.title }}" title="{{ session.title }}" href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}" style="text-decoration: none">
                                            <em class="fa fa-pencil-square-o"></em> {{ "Edit"|get_lang }} {{ "Course"|get_lang }}
                                        </a>
                                    </h3>
                                </div>
                                {% else %}
                                <div class="title">
                                    <h3>
                                        <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}" style="text-decoration: none">{{ session.title }}</a>
                                        <a class="pull-right session-info" alt="{{ session.title }}" title="{{ session.title }}" href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}" style="text-decoration: none">
                                            <em class="fa fa-pencil-square-o"></em> {{ "Edit"|get_lang }} {{ "Course"|get_lang }}
                                        </a>
                                    </h3>
                                </div>
                            {% endif %}
                        {% else %}
                        <div class="title">
                            <h3>{{ session.title }}</h3>
                        </div>    
                        {% endif %}
                    </div>
                    <div class="col-md-4">
                        {% for field_value in session.extra_fields %}
                        <div class="thumbnail">
                            {% if field_value.field.variable == 'image' %}
                                <img id="session_img_{{ session.id }}" src="{{ _p.web_upload ~ field_value.value }}"/>
                                <span class="course-metadata">
                                    <span class="category">{{ session.category_name }}</span>
                                </span>
                            {% else %}
                    
                            {% endif %}
                        </div>
                        {% endfor %}
                    </div>
                    <div class="col-md-8">
                        <div class="description">
                            <div class="text">
                                {{ session.description }}
                                <ul class="fa-ul courses">
                                    {% for course in session.courses %}
                                        <li>
                                            <i class="session-course-list-fa fa-li fa fa-book"></i> <a class="session-course-list-text" href="{{ course.link }}" style="text-decoration: none">{{ course.title }}</a>
                                        </li>
                                    {% endfor %}
                                </ul>
                            </div>
                            <div class="col-md-2">
                                <span class="session-info"><em class="fa fa-lg fa-user"></em> {{ session.session_users }}</span>
                            </div>
                            <div class="col-md-5">
                                <span class="session-info"><em class="fa fa-lg fa-calendar"></em> {{ 'StartTimeWindow' | get_lang }} : {{ session.display_start_date }}</span>
                            </div>
                            <div class="col-md-5">
                                <span class="session-info"><em class="fa fa-lg fa-calendar"></em> {{ 'EndTimeWindow' | get_lang }} : {{ session.display_end_date }}</span>
                            </div> 
                        </div>
                    </div>
                </div> 
            </div>
        </div>
{% endif %}