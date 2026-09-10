const utils = {



    getFileTypeIcon(filename) {
        let icon = 'mdi-file';

        if (!filename) {
            return icon;
        }

        let ext = filename.split('.').pop();

        if (ext === 'pdf') {
            icon = 'mdi-file-pdf';
        } else if (ext === 'doc' || ext === 'docx') {
            icon = 'mdi-file-word';
        } else if (ext === 'xls' || ext === 'xlsx') {
            icon = 'mdi-file-excel';
        } else if (ext === 'ppt' || ext === 'pptx') {
            icon = 'mdi-file-powerpoint';
        } else if (ext === 'txt' || ext === 'rtf' || ext === 'csv') {
            icon = 'mdi-file-document';
        } else if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') {
            icon = 'mdi-file-image';
        } else {
            icon = 'mdi-file';
        }

        return icon;
    },

    getFileTypeColor(filename) {
        let color = '#494949';

        if (!filename) {
            return color;
        }

        let ext = filename.split('.').pop();

        if (ext === 'pdf') {
            color = '#e30404';
        } else if (ext === 'doc' || ext === 'docx') {
            color = '#0472e6';
        } else if (ext === 'xls' || ext === 'xlsx') {
            color = '#029a02';
        } else if (ext === 'ppt' || ext === 'pptx') {
            color = '#e37902';
        } else if (ext === 'txt') {
            color = '#494949';
        } else {
            color = '#494949';
        }

        return color;
    }
}

export default utils;
