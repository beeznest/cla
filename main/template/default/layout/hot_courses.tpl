{% if hot_courses is not null and hot_courses is not empty %}

<script>
$(document).ready( function() {
    $('.star-rating li a').on('click', function(event) {
        var id = $(this).parents('ul').attr('id');
        $('#vote_label2_' + id).html("{{'Loading'|get_lang}}");
        $.ajax({
            url: $(this).attr('data-link'),
            success: function(data) {
                $("#rating_wrapper_"+id).html(data);
                if (data == 'added') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
                if (data == 'updated') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
            }
        });
    });
});
</script>
<section id="hot_courses">
    <div class="hot-course-head">
        <h4 class="hot-course-title">
            {{ "HottestCourses"|get_lang}}
            {% if _u.is_admin %}
            <span class="pull-right">
                <a title="{{ "Hide"|get_lang }}" alt="{{ "Hide"|get_lang }}" href="{{ _p.web_main }}admin/settings.php?search_field=show_hot_courses&submit_button=&_qf__search_settings=&category=search_setting">
                    <img src="{{ "eyes.png"|icon(22) }}" width="22" height="22">
                </a>
            </span>
            {% endif %}
        </h4>
    </div>
    <div id="hot-course">
        <div class="row">
            {% include template ~ '/layout/hot_course_item.tpl' %}
        </div>
    </div>
</section>
{% endif %}

{% if hot_services is not null and hot_services is not empty %}
    <section id="hot_courses">
    <div class="hot-course-head">
        <h4 class="hot-course-title">
            {{ "HottestSubscriptions" | get_plugin_lang('BuyCoursesPlugin') }}
        </h4>
    </div>
    <div id="hot-course">
        <div class="row">
            {% for hot_service in hot_services %}
                <div class="col-md-4">
                    <div class="items-course">
                        <div class="items-course-image">
                            <a href="{{ _p.web }}service/{{ hot_service.service.id }}/information"><img class="img-responsive" src="{{ _p.web_plugin }}buycourses/uploads/services/images/{{ hot_service.service.image }}" alt="{{ hot_service.service.name }}"/></a>
                        </div>
                        <div class="items-course-info">
                            <h4 class="title">
                                <a title="{{ hot_service.service.name }}" href="{{ _p.web }}service/{{ hot_service.service.id }}/information" >
                                    {{ hot_service.service.name }}
                                </a>
                            </h4>
                            <ul class="list-unstyled">
                                <li><em class="fa fa-clock-o"></em> {{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }} : {{ hot_service.service.duration_days }} {{ 'Days' | get_lang }}</li>
                            </ul>
                            <p class="lead text-center">{{ hot_service.currency == 'BRL' ? 'R$' : hot_service.currency }} {{ hot_service.service.price }}</p>
                            <div class="toolbar">
                                <a class="btn btn-info btn-block btn-sm" title="" href="{{ _p.web }}service/{{ hot_service.service.id }}/information">
                                    <em class="fa fa-info-circle"></em> {{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                                <a class="btn btn-success btn-block btn-sm" title="" href="{{ _p.web_plugin ~ 'buycourses/src/service_process.php?' ~ {'i': hot_service.service.id, 't': hot_service.service.applies_to}|url_encode() }}">
                                    <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
</section>
{% endif %}
