<script setup>
import { reactive, watch } from 'vue';
import { useDropzone } from 'vue3-dropzone';

const state = reactive({
  files: [],
});

const onDrop = (acceptFiles, rejectReasons) => {
  console.log("acceptFiles", acceptFiles);
  console.log("rejectReasons", rejectReasons);
  state.files = acceptFiles;
};

const handleClickDeleteFile = (index) => {
  state.files.splice(index, 1);
};

const { getRootProps, getInputProps, isDragActive, ...rest } = useDropzone({
  onDrop,
  accept: ['application/json'],
  maxSize: (2 * 1024 * 1024),
});

watch(state, () => {
  console.log('state', state);
  if (state.files.length > 0) {
    console.log('DO UPLOAD');
    const formData = new FormData(); // pass data as a form
    state.files.map(file => {
        formData.append("uploadedFile", file);
    });
    console.log(formData)
    axios
        .post('/api/verify', formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(response => console.log(response))
        .catch(error => console.log(error));
    }
});

watch(isDragActive, () => {
  console.log('isDragActive', isDragActive.value, rest);
});
</script>

<style lang="scss" scoped>
.dropzone,
.files {
  width: 100%;
  max-width: 300px;
  margin: 0 auto;
  padding: 10px;
  border-radius: 8px;
  box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px,
    rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
  font-size: 12px;
  line-height: 1.5;
}

.border {
  border: 2px dashed #ccc;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  transition: all 0.3s ease;
  background: #fff;

  &.isDragActive {
    border: 2px dashed #ffb300;
    background: rgb(255 167 18 / 20%);
  }
}

.file-item {
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgb(255 167 18 / 20%);
  padding: 7px;
  padding-left: 15px;
  margin-top: 10px;

  &:first-child {
    margin-top: 0;
  }

  .delete-file {
    background: red;
    color: #fff;
    padding: 5px 10px;
    border-radius: 8px;
    cursor: pointer;
  }
}
</style>
<template>
    <div>
      <div v-if="state.files.length > 0" class="files">
        <div class="file-item" v-for="(file, index) in state.files" :key="index">
          <span>{{ file.name }}</span>
          <span class="delete-file" @click="handleClickDeleteFile(index)"
            >Delete</span
          >
        </div>
      </div>
      <div v-else class="dropzone" v-bind="getRootProps()">
        <div
          class="border"
          :class="{
            isDragActive,
          }"
        >
          <input v-bind="getInputProps()" />
          <p v-if="isDragActive">Drop the files here ...</p>
          <p v-else>Drag and drop files here, or Click to select files</p>
        </div>
      </div>
    </div>
  </template>
