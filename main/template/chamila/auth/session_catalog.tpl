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
        <div class="row">
            <div class="col-md-12">
                {% if show_sessions %}
                    <div class="section-title-catalog">{{ 'Courses'|get_lang }}</div>

                    <div class="row">
                        <div class="col-md-6">
                            <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=display_sessions">
                                <div class="form-group">
                                    <label class="col-sm-3">{{ "ByDate"|get_lang }}</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="date" name="date" id="date" class="form-control" value="{{ search_date }}" readonly>
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=search_tag">
                                <div class="form-group">
                                    <label class="col-sm-4">{{ "ByTag"|get_lang }}</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" name="search_tag" class="form-control" value="{{ search_tag }}" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                {% endif %}
            </div>
        </div>
    </div>

    <section id="session-list">
        <div class="container">
            <div class="row">
                
                {% for session in sessions %}
                
                    <div class="col-xs-12 col-sm-4 col-md-3">
                        <div class="item-session" id="items-session-{{ session.id }}">
                             
                            <div class="thumbnail">
                                <img src="{{ session.image ? _p.web_upload ~ session.image : _p.web_img ~ 'session_default.png' }}">
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
                                    {% if not _u.logged %}
                                        <p>
                                            <a class="btn btn-info btn-block btn-sm" href="{{ "#{_p.web}session/#{session.id}/about/" }}" title="{{ session.name }}">{{ 'SeeCourseInformationAndRequirements'|get_lang }}</a>
                                        </p>
                                    {% else %}
                                        
                                        <p class="buttom-subscribed">
                                            {% if session.is_subscribed %}
                                                {{ already_subscribed_label }}
                                            {% else %}
                                                {{ session.subscribe_button }}
                                            {% endif %}
                                        </p>
                                    {% endif %}
                                </div>
                            </div>
                            

                            {% if _u.logged %}
                                <script>
                                    $('#session-{{ session.id }}-sequences').popover({
                                        placement: 'bottom',
                                        html: true,
                                        trigger: 'click',
                                        content: function () {
                                            var content = '';

                                            {% if session.sequences %}
                                                {% for sequence in session.sequences %}
                                                    content += '<p class="lead">{{ sequence.name }}</p>';

                                                    {% if sequence.requirements %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'RequiredSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for requirement in sequence.requirements %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ requirement.id ~ '/about/' }}">{{ requirement.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if sequence.dependencies %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'DependentSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for dependency in sequence.dependencies %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ dependency.id ~ '/about/' }}">{{ dependency.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if session.sequences|length > 1 %}
                                                        content += '<hr>';
                                                    {% endif %}
                                                {% endfor %}
                                            {% else %}
                                                content = "{{ 'NoDependencies'|get_lang }}";
                                            {% endif %}

                                            return content;
                                        }
                                    });
                                </script>
                            {% endif %}
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
