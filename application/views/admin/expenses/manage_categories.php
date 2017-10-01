<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                      <div class="_buttons">
                        <a href="#" onclick="new_category(); return false;" class="btn btn-info pull-left display-block"><?php echo _l('new_expense_category'); ?></a>
                    </div>
                    <div class="clearfix"></div>
                    <hr class="hr-panel-heading" />
                    <div class="clearfix"></div>
                    <?php render_datatable(array(_l('name'),_l('dt_expense_description'),_l('options')),'expenses-categories'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div class="modal fade" id="category" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('expenses/category')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit_expense_category'); ?></span>
                    <span class="add-title"><?php echo _l('new_expense_category'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name','expense_add_edit_name'); ?>
                        <?php echo render_textarea('description','expense_add_edit_description'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
        </div><!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php init_tail(); ?>
<script>
    $(function(){
        initDataTable('.table-expenses-categories', window.location.href, [2], [2]);
        _validate_form($('form'),{name:'required'},manage_categories);
        $('#category').on('hidden.bs.modal', function(event) {
            $('#additional').html('');
            $('#category input').val('');
            $('#category textarea').val('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });
    });
    function manage_categories(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            $('.table-expenses-categories').DataTable().ajax.reload();
            $('#category').modal('hide');
        });
        return false;
    }

    function new_category(){
        $('#category').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_category(invoker,id){
        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#additional').append(hidden_input('id',id));
        $('#category input[name="name"]').val(name);
        $('#category textarea').val(description);
        $('#category').modal('show');
        $('.add-title').addClass('hide');
    }

</script>
</body>
</html>
