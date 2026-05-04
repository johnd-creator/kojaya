<script setup lang="ts">
import { computed } from "vue";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

type SelectOption = {
  label: string;
  value: string | number | boolean | null;
};

const emptyValue = "__empty__";

const props = withDefaults(
  defineProps<{
    options: SelectOption[];
    placeholder?: string;
    class?: string;
  }>(),
  {
    placeholder: "Pilih opsi",
    class: "w-full sm:max-w-[220px]",
  },
);

const model = defineModel<string | number | boolean | null>({ default: "" });

const selectValue = computed(() => {
  if (model.value === "" || model.value === null || model.value === undefined) {
    return emptyValue;
  }

  return String(model.value);
});

const updateValue = (value: unknown): void => {
  const selected = String(value);

  if (selected === emptyValue) {
    model.value = "";

    return;
  }

  const matchedOption = props.options.find(
    (option) => String(option.value) === selected,
  );
  model.value = matchedOption?.value ?? selected;
};
</script>

<template>
  <Select :model-value="selectValue" @update:model-value="updateValue">
    <SelectTrigger :class="props.class">
      <SelectValue :placeholder="placeholder" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem
        v-for="option in options"
        :key="String(option.value ?? emptyValue)"
        :value="
          option.value === '' || option.value === null
            ? emptyValue
            : String(option.value)
        "
      >
        {{ option.label }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
