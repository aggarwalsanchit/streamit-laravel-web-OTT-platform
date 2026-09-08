<div class="modal fade" id="currencyModal" tabindex="-1" aria-labelledby="currencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-md-down">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="currencyModalLabel">{{ __('currency.lbl_add') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="currencyForm" action="{{ route('backend.currencies.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" value="POST">


             <div class="form-group">
              <label for="currencyName" class="form-label">{{ __('currency.lbl_currency_name') }} <span class="text-danger">*</span></label>
              <div class="position-relative">
                <select id="currencyName" name="currency_name" class="form-select">
                <option value="">Select Currency</option>
                @foreach ($curr_names as $curr)
                  <option value="{{ $curr['currency_name'] }}">{{ $curr['currency_name'] }}</option>
                @endforeach
              </select>
              </div>
              @error('currency_name')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="currency-name-error"></div>
            </div>
            <div class="form-group">
              <label for="currencySymbol" class="form-label">{{ __('currency.lbl_currency_symbol') }} <span class="text-danger">*</span></label>
              <input type="text" name="currency_symbol" placeholder="{{ __('currency.lbl_currency_symbol') }}" id="currencySymbol" class="form-control " value="{{ old('currency_symbol') }}">
              @error('currency_symbol')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="currency-symbol-error"></div>
            </div>
            <div class="form-group">
              <label for="currencyCode" class="form-label">{{ __('currency.lbl_currency_code') }} <span class="text-danger">*</span></label>
              <input type="text" name="currency_code" placeholder="{{ __('currency.lbl_currency_code') }}" id="currencyCode" class="form-control " value="{{ old('currency_code') }}">
              @error('currency_code')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="currency-code-error"></div>
            </div>
            <div class="form-group">
              <div class="d-flex justify-content-between align-items-center">
                <label for="isPrimary" class="form-label">{{ __('currency.lbl_is_primary') }} <span class="text-danger">*</span></label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary" value="1">
                </div>
              </div>
            </div>
            <h6><b>{{ __('currency.currency_format') }}</b></h6>
            <div class="form-group">
              <label for="currencyPosition" class="form-label">{{ __('currency.lbl_currency_position') }}</label>
              <div class="position-relative">
                <select name="currency_position" id="currencyPosition" class="form-select">
                <option value="left">{{ __('messages.Left') }}</option>
                <option value="right">{{ __('messages.Right') }}</option>
                <option value="left_with_space">{{ __('messages.Left With Space') }}</option>
                <option value="right_with_space">{{ __('messages.Right With Space') }}</option>
              </select>
              </div>
            </div>
            <div class="form-group">
              <label for="thousandSeparator" class="form-label">{{ __('currency.lbl_thousand_separatorn') }} <span class="text-danger">*</span></label>
              <input type="text" name="thousand_separator" placeholder="{{ __('currency.lbl_thousand_separatorn') }}" id="thousandSeparator" class="form-control" value="{{ old('thousand_separator') }}">
              @error('thousand_separator')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="thousand-separator-error"></div>
            </div>
            <div class="form-group">
              <label for="decimalSeparator" class="form-label">{{ __('currency.lbl_decimal_separator') }} <span class="text-danger">*</span></label>
              <input type="text" name="decimal_separator" placeholder="{{ __('currency.lbl_decimal_separator') }}" id="decimalSeparator" class="form-control" value="{{ old('decimal_separator') }}">
              @error('decimal_separator')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="decimal-separator-error"></div>
            </div>
            <div class="form-group">
              <label for="noOfDecimal" class="form-label">{{ __('currency.lbl_number_of_decimals') }} <span class="text-danger">*</span></label>
              <input type="number" name="no_of_decimal" placeholder="{{ __('currency.lbl_number_of_decimals') }}" id="noOfDecimal" class="form-control" value="{{ old('no_of_decimal') }}">
              @error('no_of_decimal')
                <span class="text-danger">{{ $message }}</span>
              @enderror
              <div class="invalid-feedback" id="no-of-decimal-error"></div>
            </div>
          </form>
        </div>
        <div class="border-top">
          <div class="d-grid d-md-flex justify-content-end gap-3 p-3">
            <button type="submit" form="currencyForm" class="btn btn-primary d-block">
              {{ __('messages.save') }}
            </button>

          </div>
        </div>
      </div>
    </div>
  </div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

<script>
$(document).ready(function() {
    $('#currencyName').on('change', function() {
        var currencyName = $(this).val();

        if(currencyName) {
            $.ajax({
                url: '{{ route("backend.currencies.getCurrencyData") }}',
                type: 'GET',
                data: {currency_name: currencyName},
                success: function(data) {
                    $('#currencySymbol').val(data.currency_symbol);
                    $('#currencyCode').val(data.currency_code);
                }
            });
        }else{

            $('#currencySymbol').val('');
            $('#currencyCode').val('');

        }
    });
});
</script>
