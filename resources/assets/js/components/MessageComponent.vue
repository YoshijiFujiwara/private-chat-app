<template>
    <div class="card card-default chat-box">
        <div class="card-header">
            <b :class="{'text-danger': session_block}">
                {{ friend.name }}
                <span v-if="session_block">(ブロック中)</span>
            </b>

            <!-- 閉じる -->
            <a href="" @click.prevent="close">
                <i class="fa fa-times float-right" aria-hidden="true"></i>
            </a><!-- 閉じる -->

            <!-- オプション -->
            <div class="dropdown float-right mr-4">
                <!-- <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Dropdown button
                </button> -->
                <a href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-ellipsis-v px-4" aria-hidden="true"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" v-if="session_block"
                    @click.prevent="unblock">ブロック解除</a>
                    <a class="dropdown-item" href="#" @click.prevent="block" v-else>ブロック</a>
                    <a class="dropdown-item" href="#" @click.prevent="clear">チャットをクリア</a>
                </div>
            </div>

        </div>
        <div class="card-body" v-chat-scroll>
            <p class="card-text"
               v-for="chat in chats"
               :key="chat.id"
               :class="{'text-right':chat.type == 0, 'text-success':chat.read_at!=null}">
                {{ chat.message }}
                <br>
                <span style="font-size:8px;">{{chat.read_at}}</span>
            </p>
        </div>

        <form class="card-footer" @submit.prevent="send">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="メッセージをかいて"
                :disabled="session_block" v-model="message">
            </div>
        </form>
    </div>
</template>

<script>
export default {
    props: ['friend'],
    data() {
        return {
            chats: [],
            message: null,
            session_block: false
        }
    },
    methods: {
        send() {
            if (this.message) {
                this.pushToChats(this.message);
                axios.post(`/send/${this.friend.session.id}`, {
                  content: this.message,
                  to_user: this.friend.id
                })// レスポンスのデータと交換することで'read_at'を得る
                .then(res => this.chats[this.chats.length - 1].id = res.data)

                this.message = null;
            }
        },
        pushToChats(message) {
            this.chats.push({ message: message, type: 0, read_at:null, send_at: 'たった今'});
        },
        close() {
            this.$emit('close');
        },
        clear() {
            axios.post(`/session/${this.friend.session.id}/clear`).then(res => this.chats = []);
        },
        block() {
            this.session_block = true;
        },
        unblock() {
            this.session_block = false;
        },
        getAllMessages() {
          axios.get(`/session/${this.friend.session.id}/chats`)
              .then(res => this.chats = res.data.data)
        },
        read() {
            axios.get(`/session/${this.friend.session.id}/read`);
        }
    },
    created() {
        this.read();
        this.getAllMessages();

        Echo.private(`Chat.${this.friend.session.id}`).listen('PrivateChatEvent', e => {
            if (this.friend.session.open) this.read(); // ?????
            this.chats.push({message: e.content, type: 1, send_at: "たった今"})
        });

        Echo.private(`Chat.${this.friend.session.id}`).listen('MessageReadEvent', e => {
            this.chats.forEach(chat => chat.id == e.chat.id ? chat.read_at = e.chat.read_at : '')
        });
    }
}
</script>

<style>
.chat-box {
    height: 400px;
}
.card-body {
    overflow-y: scroll;
}
</style>

