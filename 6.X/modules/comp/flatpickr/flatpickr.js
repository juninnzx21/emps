(function() {

    EMPS.vue_component_direct('flatpickr', {
        template: '#flatpickr-component-template',
        props: ['size', 'value', 'modelValue', 'hasTime', 'minDate', 'maxDate', 'setclass',
            'hasClock', 'unix', 'mformat', 'fw',
            'dateFormat', 'placeholder', 'asButton', 'asSlot'],
        emits: ['update:modelValue'],
        data: function(){
            return {
                picker: null,
                set_class: '',
                fdate: '',
                config: window.emps_flatpickr_options,
            };
        },
        methods: {
            redraw: function(newConfig) {
                this.picker.config = Object.assign(this.picker.config, newConfig);
                this.picker.config.minDate = this.minDate;
                this.picker.config.maxDate = this.maxDate;
                this.picker.config.disableMobile = true;
                this.picker.redraw();
                this.picker.jumpToDate();
            },
            set_date: function(newDate, oldDate) {
//                alert(newDate + " / " + oldDate);
                if (this.unix) {
                    if ((newDate !== oldDate) && newDate !== undefined && newDate != '' && newDate != 0 && newDate != null && !isNaN(newDate)) {
                        let m = moment.unix(newDate);
                        if (window.timezone !== undefined) {
                            m.tz(window.timezone);
                        }

                        let fdate = m.format(this.mformat);
                        this.fdate = fdate;
                        this.picker.setDate(fdate);
                        //console.log("Setting date (unix): " + newDate + " (" + fdate + ") / " + oldDate);
                    }
                    if (newDate === undefined || newDate == '') {
                        this.picker.clear();
                        $(this.$refs.input).val('');
                    }
                } else {
                    if ((newDate !== oldDate) && newDate !== undefined && newDate != '') {
                        this.picker.setDate(newDate);
                        //console.log("Setting date: " + newDate + " / " + oldDate);
                    }
                    if (newDate === undefined || newDate == '') {
                        this.picker.clear();
                        $(this.$refs.input).val('');
                    }
                }

            },
            date_updated: function(selectedDates, dateStr) {
                if (dateStr !== undefined && dateStr != '') {
                    //console.log("Date updated: "  + dateStr);
                    if (this.unix) {
                        var date;
                        if (window.timezone !== undefined) {
                            date = moment.tz(dateStr, this.mformat, window.timezone);
                        } else {
                            date = moment(dateStr, this.mformat);
                        }

                        var edt = date.unix();
                        //console.log("Emitting " + edt);
                        if (EMPS.vue_version() == 3) {
                            this.$emit('update:modelValue', edt)
                        } else {
                            this.$emit("input", edt);
                        }
                    } else {
                        if (EMPS.vue_version() == 3) {
                            this.$emit('update:modelValue', dateStr)
                        } else {
                            this.$emit("input", dateStr);
                        }
                    }

                }
            }
        },
        mounted: function(){
            this.config.minDate = this.minDate;
            this.config.maxDate = this.maxDate;
            if (!this.picker) {
                this.config.onValueUpdate = this.date_updated;
                this.config.disableMobile = true;
                var dateFormat = "d.m.Y";
                if (this.dateFormat !== undefined) {
                    dateFormat = this.dateFormat;
                }
                if (this.config.dateFormat !== undefined) {
                    dateFormat = this.config.dateFormat;
                }
                if (this.hasTime) {
                    this.config.enableTime = true;
                    if (dateFormat.indexOf("H:i") == -1) {
                        this.config.dateFormat = dateFormat + " H:i";
                    } else {
                        this.config.dateFormat = dateFormat;
                    }

                } else {
                    this.config.enableTime = false;
                    this.config.dateFormat = dateFormat;
                }

                this.picker = flatpickr(this.$refs.input, this.config);
            }
            //setTimeout(this.set_date, 100, this.value);
            if (EMPS.vue_version() == 3) {
                this.set_date(this.modelValue);
            } else {
                this.set_date(this.value);
            }

            this.$watch('minDate', this.redraw);
            this.$watch('maxDate', this.redraw);
            this.$watch('config', this.redraw);
            this.$watch('value', this.set_date);
            this.$watch('setclass', function(newval, oldval) {
                this.set_class = newval;
            });
        }
    });


})();
