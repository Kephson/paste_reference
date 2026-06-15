import { vi } from 'vitest';

export default class AjaxRequest {
  constructor(url) {
    this.url = url;
    this.queryArgs = {};
  }

  withQueryArguments(args) {
    this.queryArgs = args;
    return this;
  }

  post(data) {
    return Promise.resolve({
      resolve: () => Promise.resolve({
        success: true,
        data: {
          tabs: [{ 
            items: [{ 
              identifier: 'tt_content|123', 
              title: 'Test Element',
              uid: 123
            }] 
          }],
          copyMode: 'copy'
        }
      })
    });
  }

  get() {
    return this.post();
  }
}