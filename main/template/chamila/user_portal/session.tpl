{% if not session.show_simple_session_info %}
        <div class="col-md-12">
            <div class="item-session">
                <div class="row">
                    <div class="col-md-4">
                        {% for field_value in session.extra_fields %}
                        <div class="thumbnail">
                            {% if field_value.field.variable == 'image' %}
                            <img id="session_img_{{ session.id }}" src="{{ _p.web_upload ~ field_value.value }}"/>
                            <span class="course-metadata">
                                <span class="category">Diseño</span>
                            </span>
                            {% else %}
                    
                            {% endif %}
                        </div>
                        {% endfor %}
                    </div>
                    <div class="col-md-8">
                        <div class="description">
                        {% if session.show_link_to_session %}
                            {% if session.courses|length == 1 %}
                                {% set course = session.courses|first %}
                                <div class="title">
                                    <h3><a href="{{ course.link }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a></h3>
                                </div>
                                {% else %}
                                <div class="title">
                                    <h3><a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a></h3>
                                </div>
                            {% endif %}
                        {% else %}
                        <div class="title">
                            <h3>{{ session.title }}</h3>
                        </div>    
                        {% endif %}
                        <div class="text">
                            {{ session.description }}
                            <ul class="courses">
                                {% for course in session.courses %}
                                <li>
                                    <i class="fa fa-book"></i> <a title="{{ course.title }}" href="{{ course.link }}">{{ course.title }}</a>
                                </li>

                                {% endfor %}
                            </ul>
                        </div>
                        <div class="info">
                            <a class="btn btn-block btn-default" alt="{{ session.title }}" title="{{ session.title }}" href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                                <i class="fa fa-pencil"></i> {{ "Edit"|get_lang }}
                            </a>
                        </div>
                </div>
            </div>
        </div> 
    </div>
</div>
{% endif %}