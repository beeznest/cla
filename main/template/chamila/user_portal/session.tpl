{% if not session.show_simple_session_info %}
        
            <div class="list-session">
                <div class="row">
                    <div class="col-xs-12 col-md-8">
                        {% if session.show_link_to_session %}
                            {% if session.courses|length == 1 %}
                                {% set course = session.courses|first %}
                                <div class="title">
                                    <h3>
                                        <a href="{{ course.link }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a>
                                    </h3>
                                </div>
                                {% else %}
                                <div class="title">
                                    <h3>
                                        <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a>
                                        
                                    </h3>
                                </div>
                            {% endif %}
                        {% else %}
                        <div class="title">
                            <h3>{{ session.title }}</h3>
                        </div>    
                        {% endif %}
                    </div>
                    <div class="col-xs-12 col-md-4">
                        <div class="tools pull-right">
                            <a class="btn btn-xs btn-default" alt="{{ session.title }}" title="{{ session.title }}" href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                                <em class="fa fa-pencil-square-o"></em> {{ "Edit"|get_lang }} {{ "Course"|get_lang }}
                            </a>
                        </div> 
                    </div>
                </div>
                <div class="row">
                    
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
                        <div class="session-description">
                            <div class="text">
                                {{ session.description }}
                                
                            </div>
                            <div class="list">
                                <ul class="fa-ul courses">
                                    
                                {% for course in session.courses %}
                                    <li>
                                        <i class="fa-li fa fa-book"></i> <a href="{{ course.link }}" title="{{ course.title }}" >{{ course.title }} {{ course.notifications }}</a>
                                    </li>
                                {% endfor %}
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-md-2">
                                    <span class="session-info"><em class="fa fa-lg fa-user"></em> {{ session.session_users }}</span>
                                </div>
                                <div class="col-xs-12 col-md-5">
                                    <span class="session-info"><em class="fa fa-lg fa-calendar"></em> {{ 'StartTimeWindow' | get_lang }} : {{ session.display_start_date }}</span>
                                </div>
                                <div class="col-xs-12 col-md-5">
                                    <span class="session-info"><em class="fa fa-lg fa-calendar"></em> {{ 'EndTimeWindow' | get_lang }} : {{ session.display_end_date }}</span>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        
{% endif %}