<template>
  <div class="copy-page">
    <v-header title="Clone collection items"></v-header>
    <div v-if="isLoading" class="overlay">copying...</div>
     <v-select
      :label="options.placeholder"
      :disabled="readonly"
      :options="collections"
      :placeholder="options.placeholder"
      :icon="options.icon"
      v-model="collection"
      v-on:input="loadList"
    ></v-select>

    <table v-if="list">
        <tr>
          <th v-for="field in fields" v-bind:key="field.label">{{ field.name }}</th>
          <th>Tags for Copy</th>
          <th>Actions</th>
        </tr>
        <tr v-for="item in list" v-bind:key="item.id">
          <td>{{ item[fields[0].name] }}</td>
          <td>{{ item[fields[1].name] }}</td>
          <td>{{ item[fields[2].name] }}</td>
          <td>{{ item[fields[3].name] }}</td>
          <td>
            <div class="tags-input v-input input icon-right">
              <input v-bind:data-model="item.id"  @keyup.enter="addTag($event)"/>
              <i class="" style="font-size: 24px;">local_offer</i>

            </div>
            <div class="tags">
              <span class="tag" @click="removeTag(tag,item.id)" v-for="tag in tags[item.id]" v-bind:key="tag">
                {{ tag }}
              </span>
            </div>
          </td>
          <td>
            <button class="copy" @click="callCopy(item.id)">Clone</button>
          </td>
        </tr>
    </table>
    
    </div>
</template>

<script>

import Vue from 'vue'
import axios from 'axios'
import VueAxios from 'vue-axios'
 
Vue.use(VueAxios, axios)

export default {

  name: "copy-page",
  data: () => ({
    items: [],
    isLoading: false,
    collection: '',
    collection_item: '',
    collections: [],
    tags: [],
    list:[],
    options: {
      placeholder: 'Select a Collection',
      itemsplaceholder: 'Select a Collection Item',
      
    }
  }),
  computed: {
    
    fields() {

      var keys = this.list[0] ? Object.keys(this.list[0]) : "";
      var fields = [];
      var counter = 0;
      Array.from(keys).forEach(function(item) {
          if(counter < 4) {
            fields.push({
              'field': item,
              'name': item,
            })
          }
          counter ++;
      });
      
      return fields;

      /*return [
        {'field':'name','name':'Name'},{'field':'identifier','name':'Identifier'}
      ];*/
    }
  },
  created() {
    var _this = this;

    this.$api.getItems('directus_collections').then(function(items) {
      
      Array.from(items.data).forEach(function(key, value) {
        if(!items.data[value].hidden && items.data[value].managed) {
          _this.collections.push(items.data[value].collection);
        }
      });
    })
  },
  methods: {
    removeTag(tag,id) {
      var tags = this.tags[id].filter(function(item) {
          return item !== tag
      });
      this.tags[id] = tags;
      this.$forceUpdate();
    },
    addTag(e) { 
      var id = e.target.getAttribute('data-model');
      if(this.tags[id]) {
        console.log('yes');
        this.tags[id].push(e.target.value);
      } else {
        console.log('no');
        this.tags[id] = [];
        this.tags[id].push(e.target.value);
      }
      e.target.value = '';
      this.$forceUpdate();
      //tags.push(e.target.value);
    },
    tagHandler(event) {
      
      console.log(event);
    },
    callCopy(id) {
      var tags = this.tags[id] ? this.tags[id].join(',') : '';
      this.isLoading = true;
      Vue.axios.get('/_/custom/duplicate?access_token=micimacko&id='+id+'&collection='+this.collections[this.collection]+'&tags='+tags).then((response) => {
       if(response.data.data.status == 'error') {
        this.$notify({title:response.data.data.message,color:"red",iconMain:"error"})

       } else {
          this.$notify({title:'Succesfully cloned',color:"green",iconMain:"done_all"})
          this.loadList();
       }
        this.isLoading = false;
      })
      .catch((error) => {
         this.$notify({title:error,color:"red",iconMain:"error"})
         this.isLoading = false;
      });
    },
    loadList() { 
      
      console.log(this.collections[this.collection]);
      this.list = [];
      this.$api.getItems(this.collections[this.collection]).then(list => this.list = list.data )
      
    },
    selectCollectionItem() {
      //this.$api.getItems(this.collections[this.collection]).then(list => this.list = list.data )

      console.log(this.list[this.collection_item]);
    },
    toProperCase(str) {
      return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
    },
  }
  
};
</script>

<style lang="scss" scoped>
.copy-page {
  padding: var(--page-padding);

  h1 {
    margin-bottom: 20px;
  }

  table {
    margin-top: 20px;
    width: 100%;
    border-top: 1px solid #d5d5d5;
    th, td {
      text-align: center;
    }
    td {
      border-bottom: 1px solid #d5d5d5;
      padding-top: 15px;
      padding-bottom: 15px;
    }
    th{
      font-weight: bold;
      padding: 20px 0px;
      border-bottom: 1px solid #d5d5d5;
    }
  }

  .copy {
    background: #222;
    color: #fff;
    height: 32px;
    padding: 10px 20px;
    text-align: center;
    line-height: 13px;
    border-radius: 3px;
    font-size: 12px;
  }

  .tags-input {
    text-align: left;
    position: relative;
      width: 100%;

    i {
        right: 10px;
        position: absolute;
        top: 50%;
        color: var(--lighter-gray);
        -webkit-transform: translateY(-50%);
        transform: translateY(-50%);
        font-size: 24px;
        font-family: Material Icons;
        font-weight: 400;
        font-style: normal;
        display: inline-block;
        line-height: 1;
        text-transform: none;
        letter-spacing: normal;
        word-wrap: normal;
        white-space: nowrap;
        -webkit-font-feature-settings: "liga";
        font-feature-settings: "liga";
        vertical-align: middle;
    }
    input {
      width: 100%;
      border: var(--input-border-width) solid var(--lighter-gray);
      border-radius: var(--border-radius);
      color: var(--gray);
      padding: 10px;
      font-size: 1rem;
      line-height: 1.5;
      text-transform: none;
      -webkit-transition: var(--fast) var(--transition);
      transition: var(--fast) var(--transition);
      -webkit-transition-property: color,border-color,padding;
      transition-property: color,border-color,padding;
      height: var(--input-height);
    }
  }

  .tags {
    margin-top: 15px;
    text-align: left;
    .tag {
      cursor:pointer;
      padding: 6px;
      background-color: var(--lightest-gray);
      border-radius: 2px;
      text-align: center;
      color: #444444;
      margin-right: 3px;
      &:hover {
        background-color: var(--danger);
        color: white;
      }
    }
  }

  .overlay {
    position: absolute;
    background: rgba(0,0,0,0.5);
    width: 100%;
    height: 100%;
    z-index: 10000000000;
    top: 0px;
    left: 0px;
    display: flex;
    align-content: center;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    color: white;
  }
}
</style>
