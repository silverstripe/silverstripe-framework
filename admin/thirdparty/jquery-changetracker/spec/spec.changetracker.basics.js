describe 'ChangeTracker'
  describe 'Basics'
    before_each
      $('body').append(
      '<form method="GET" id="form_test" action="#"></form>'
    );
    end
    after_each
      $('#form_test').remove()
    end

    it 'doesnt mark unaltered forms as changed'
      $('#form_test').append(
        '<input type="text" name="field_text" value="origval" />'
      );

      $('#form_test').changetracker();

      $('#form_test').is('.changed').should.be_false
    end

    it 'can track changes on input type=text fields with existing values'
      $('#form_test').append(
        '<input type="text" name="field_text" value="origval" />'
      );

      $('#form_test').changetracker();

      $(':input[name=field_text]').val('newval').trigger('change');
      $('#form_test').is('.changed').should.be_true
      $(':input[name=field_text]').is('.changed').should.be_true
    end

    it 'can track changes on input type=radio fields with existing values'
      $('#form_test').append(
        '<input type="radio" id="field_radio1" name="field_radio" value="1" checked="checked" />'
        + '<input type="radio" id="field_radio2" name="field_radio" value="2" />'
      );
      $('#form_test').changetracker();
      $('#field_radio2').attr('checked', 'checked').trigger('click');
      $('#form_test').is('.changed').should.be_true
      // $('#field_radio1').is('.changed').should.be_true
      $('#field_radio2').is('.changed').should.be_true
    end

    it 'can track changes on select fields with existing values'
      $('#form_test').append(
        '<select name="field_select">'
        + '<option value="1" selected="selected" />'
        + '<option value="2" />'
        + '</select>'
      );

      $('#form_test').changetracker();

      $(':input[name=field_select]').val(2).trigger('change');
      $('#form_test').is('.changed').should.be_true
      $(':input[name=field_select]').is('.changed').should.be_true
    end

    it 'can exclude certain fields via an optional selector'
      $('#form_test').append(
        '<input type="text" name="field_text" value="origval" />'
        + '<input type="text" name="field_text_ignored" value="origval" />'
      );

      $('#form_test').changetracker({
        ignoreFieldSelector: ':input[name=field_text_ignored]'
      });

      $(':input[name=field_text_ignored]').val('newval').trigger('change');
      $('#form_test').is('.changed').should.be_false
      $(':input[name=field_text_ignored]').is('.changed').should.be_false

      $(':input[name=field_text]').val('newval').trigger('change');
      $('#form_test').is('.changed').should.be_true
      $(':input[name=field_text]').is('.changed').should.be_true
    end

    it 'can attach custom CSS classes for tracking changed state'
      $('#form_test').append(
        '<input type="text" name="field_text" value="origval" />'
      );

      $('#form_test').changetracker({
        changedCssClass: 'customchanged'
      });

      $(':input[name=field_text]').val('newval').trigger('change');
      $('#form_test').hasClass('changed').should.be_false
      $('#form_test').hasClass('customchanged').should.be_true
      $(':input[name=field_text]').hasClass('changed').should.be_false
      $(':input[name=field_text]').hasClass('customchanged').should.be_true
    end

    it 'can reset changed state of individual fields'
      $('#form_test').append(
        '<input type="text" name="field_text1" value="origval" />'
        + '<input type="text" name="field_text2" value="origval" />'
      );
      $('#form_test').changetracker();

      $(':input[name=field_text1]').val('newval').trigger('change');
      $(':input[name=field_text2]').val('newval').trigger('change');
      $('#form_test').changetracker('resetField', $(':input[name=field_text1]'));
      $(':input[name=field_text1]').is('.changed').should.be_false
      $(':input[name=field_text2]').is('.changed').should.be_true
      $('#form_test').is('.changed').should.be_true
    end

    it 'can reset all fields in the form'
      $('#form_test').append(
        '<input type="text" name="field_text1" value="origval" />'
        + '<input type="text" name="field_text2" value="origval" />'
      );
      $('#form_test').changetracker();

      $(':input[name=field_text1]').val('newval').trigger('change');
      $(':input[name=field_text2]').val('newval').trigger('change');
      $('#form_test').changetracker('reset');
      $(':input[name=field_text1]').is('.changed').should.be_false
      $(':input[name=field_text2]').is('.changed').should.be_false
      $('#form_test').is('.changed').should.be_false
    end

  end
end
