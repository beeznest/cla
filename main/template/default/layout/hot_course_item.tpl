{% for hot_course in hot_courses %}
    {% if hot_course.extra_info.title %}
                    <div class="col-md-4">
                        <div class="items-course">
                            <div class="items-course-image">
                                <a href="{{ hot_course.extra_info.course_public_url }}"><img class="img-responsive" src="{{ hot_course.extra_info.course_image_large }}" alt="{{ hot_course.extra_info.title|e }}"/></a>
                            </div>
                            <div class="items-course-info">
                                <h4 class="title">
                                    <a title="{{ hot_course.extra_info.title}}" href="{{ hot_course.extra_info.course_public_url }}" class="{{ hot_course.extra_info.services ? 'serviceCheckFont' : '' }}">
                                        {{ hot_course.extra_info.title}} {{ hot_course.extra_info.services ? '<em class="fa fa-diamond"></em>' : '' }}
                                    </a>
                                </h4>
                                <div class="teachers">{{ hot_course.extra_info.teachers }}</div>
                                <div class="ranking">
                                    {{ hot_course.extra_info.rating_html }}
                                </div>
                                <div class="toolbar">
                                    <div class="btn-group" role="group">
                                    {{ hot_course.extra_info.description_button }}
                                    {% if hot_course.extra_info.services_enable %}
                                        <a class="btn btn-warning btn-sm" title="{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}" role="button" data-toggle="popover" id="course-{{ hot_course.extra_info.real_id }}-services"> <em class="fa fa-tags"  > </em> </a>
                                    {% endif %}
                                    {{ hot_course.extra_info.register_button }}
                                    {{ hot_course.extra_info.unsubscribe_button }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        $('#course-{{ hot_course.extra_info.real_id }}-services').popover({
                            placement: 'bottom',
                            html: true,
                            trigger: 'click',
                            content: function () {
                                var content = '';

                                {% if hot_course.extra_info.services %}
                                    content += '<ul>';
                                    {% for service in hot_course.extra_info.services %}
                                        content += '<li>';
                                        content += '{{ service.service.name }}';
                                        content += '</li>';
                                    {% endfor %}
                                    content += '</ul>';
                                {% else %}
                                    content = "{{ 'NoServices'|get_plugin_lang('BuyCoursesPlugin') }}";
                                {% endif %}

                                return content;
                            }
                        });
                    </script>
    {% endif %}
{% endfor %}
