import { Doughnut, mixins } from "vue-chartjs";

const {reactiveProp} = mixins

export function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }

    return color;
}

export default {
    extends: Doughnut,
    props: ["data", "options"],
    mixins: [reactiveProp],
    mounted() {
        // this.chartData is created in the mixin.
        // If you want to pass options please create a local options object

        this.renderChart(this.chartData, {
            borderWidth: "10px",
            hoverBackgroundColor: "red",
            hoverBorderWidth: "10px",
            responsive: true
        });
    },
};
